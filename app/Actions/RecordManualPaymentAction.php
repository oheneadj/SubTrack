<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonImmutable;

/**
 * Records a payment an admin took outside of any gateway — cash, bank
 * transfer, etc. — against an invoice, supporting either a full or
 * partial amount.
 */
class RecordManualPaymentAction
{
    /**
     * @param  int  $amountCents  The amount received, in cents.
     */
    public function execute(Invoice $invoice, int $amountCents): Payment
    {
        if ($invoice->isPaid()) {
            throw new InvoiceAlreadyPaidException;
        }

        if ($amountCents <= 0 || $amountCents > $invoice->balance_due) {
            throw new InvalidPaymentAmountException;
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'gateway' => 'manual',
            'method' => 'manual',
            'amount' => $amountCents,
            'currency' => 'usd',
            'status' => PaymentRecordStatus::Succeeded,
            'paid_at' => CarbonImmutable::now(),
        ]);

        $invoice->recalculatePaymentStatus();

        return $payment;
    }
}
