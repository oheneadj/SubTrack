<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvalidPaymentDateException;
use App\Exceptions\PaymentNotEditableException;
use App\Models\Payment;
use Carbon\CarbonInterface;

/**
 * Corrects the amount and/or received date on a manually-recorded payment
 * made by mistake — only while it's still eligible for a direct edit (see
 * Payment::isEditable()). Once a receipt may already document the original
 * amount, voiding and recording a fresh payment (VoidManualPaymentAction)
 * is the only path.
 */
class EditManualPaymentAction
{
    /**
     * @param  int  $newAmountCents  The corrected amount, in cents.
     * @param  CarbonInterface|null  $newPaidAt  The corrected received date — null leaves it unchanged. Can't be in the future.
     *
     * @throws PaymentNotEditableException if the payment is no longer eligible for a direct edit
     * @throws InvalidPaymentAmountException if the new amount is invalid or would overpay the invoice
     * @throws InvalidPaymentDateException if the new date is in the future
     */
    public function execute(Payment $payment, int $newAmountCents, ?CarbonInterface $newPaidAt = null): Payment
    {
        if (! $payment->isEditable()) {
            throw new PaymentNotEditableException;
        }

        if ($newPaidAt && $newPaidAt->isFuture()) {
            throw new InvalidPaymentDateException;
        }

        // A fresh query, not the cached $payment->invoice relation — see
        // the comment in VoidManualPaymentAction for why that matters here.
        $invoice = $payment->invoice()->first();
        $otherSucceededTotal = $invoice->payments()
            ->where('status', $payment->status)
            ->where('id', '!=', $payment->id)
            ->sum('amount');

        if ($newAmountCents <= 0 || ($otherSucceededTotal + $newAmountCents) > $invoice->total_amount) {
            throw new InvalidPaymentAmountException;
        }

        $payment->update(array_filter([
            'amount' => $newAmountCents,
            'paid_at' => $newPaidAt,
        ], fn ($value) => $value !== null));
        $invoice->recalculatePaymentStatus();

        return $payment;
    }
}
