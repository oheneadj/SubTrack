<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvalidPaymentDateException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Records a payment an admin took outside of any gateway — cash, bank
 * transfer, etc. — against an invoice, supporting either a full or
 * partial amount.
 */
class RecordManualPaymentAction
{
    /**
     * @param  int  $amountCents  The amount received, in cents.
     * @param  CarbonInterface|null  $paidAt  When the payment was actually received — defaults to now. Can't be in the future.
     */
    public function execute(Invoice $invoice, int $amountCents, ?CarbonInterface $paidAt = null): Payment
    {
        if ($invoice->isPaid()) {
            throw new InvoiceAlreadyPaidException;
        }

        if ($amountCents <= 0 || $amountCents > $invoice->balance_due) {
            throw new InvalidPaymentAmountException;
        }

        $paidAt ??= CarbonImmutable::now();
        if ($paidAt->isFuture()) {
            throw new InvalidPaymentDateException;
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'gateway' => 'manual',
            'method' => 'manual',
            'amount' => $amountCents,
            'currency' => 'usd',
            'status' => PaymentRecordStatus::Succeeded,
            'paid_at' => $paidAt,
        ]);

        $invoice->recalculatePaymentStatus();

        return $payment;
    }
}
