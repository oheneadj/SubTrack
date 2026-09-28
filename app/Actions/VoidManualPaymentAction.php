<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Exceptions\PaymentNotVoidableException;
use App\Models\Payment;

/**
 * Voids a manually-recorded payment made by mistake, without ever
 * overwriting or deleting the original record — the amount and who
 * recorded it stay exactly as they were, just excluded from the
 * invoice's amount_paid going forward. This is always safe to do,
 * regardless of whether a receipt has already been issued.
 */
class VoidManualPaymentAction
{
    /**
     * @throws PaymentNotVoidableException if the payment isn't a successful manual payment
     */
    public function execute(Payment $payment, ?string $reason = null): Payment
    {
        if (! $payment->isVoidable()) {
            throw new PaymentNotVoidableException;
        }

        $payment->update([
            'status' => PaymentRecordStatus::Voided,
            'void_reason' => $reason,
        ]);

        // A fresh query, not the cached $payment->invoice relation: when a
        // Payment is created in the same request as its parent Invoice
        // (e.g. RecordManualPaymentAction immediately followed by voiding
        // it), Eloquent auto-wires the inverse relation to a snapshot of
        // that Invoice taken before recalculatePaymentStatus() first ran.
        // Calling ->recalculatePaymentStatus() on that stale snapshot computes
        // the correct values but silently fails to persist them — save()
        // compares against the stale snapshot's "original" attributes, which
        // coincidentally already match the newly computed ones, so Eloquent's
        // dirty-checking sees no change and skips the UPDATE entirely.
        $payment->invoice()->first()->recalculatePaymentStatus();

        return $payment;
    }
}
