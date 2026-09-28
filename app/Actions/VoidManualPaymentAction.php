<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Exceptions\PaymentNotVoidableException;
use App\Exceptions\VoidReasonRequiredException;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\ReceiptPdfService;

/**
 * Voids a manually-recorded payment made by mistake, without ever
 * overwriting or deleting the original record — the amount and who
 * recorded it stay exactly as they were, just excluded from the
 * invoice's amount_paid going forward. This is always safe to do,
 * regardless of whether a receipt has already been issued — any receipt
 * that no longer adds up once the void takes effect is flagged invalid
 * rather than silently left looking legitimate.
 */
class VoidManualPaymentAction
{
    public function __construct(
        private readonly ReceiptPdfService $pdfService,
    ) {}

    /**
     * @throws PaymentNotVoidableException if the payment isn't a successful manual payment
     * @throws VoidReasonRequiredException if no reason was given while Setting `require_void_reason` is on
     */
    public function execute(Payment $payment, ?string $reason = null): Payment
    {
        if (! $payment->isVoidable()) {
            throw new PaymentNotVoidableException;
        }

        $reasonRequired = filter_var(Setting::get('require_void_reason', false), FILTER_VALIDATE_BOOLEAN);
        if ($reasonRequired && ! trim((string) $reason)) {
            throw new VoidReasonRequiredException;
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
        $invoice = $payment->invoice()->first();
        $invoice->recalculatePaymentStatus();

        // Now precise: a receipt is tied to the specific payment it
        // documents, so invalidating exactly the voided payment's own
        // receipt (if any) is exact — no more inferring from whether the
        // invoice's total happens to still add up.
        $receipt = $payment->receipts()->whereNull('invalidated_at')->first();
        if ($receipt) {
            $receipt->update([
                'invalidated_at' => now(),
                'invalidated_reason' => $reason
                    ? "Underlying payment voided: {$reason}"
                    : 'Underlying payment voided',
            ]);

            // Regenerate the stored PDF so it picks up the invalidated
            // banner immediately — otherwise the old, already-rendered
            // file would keep being served with no indication anything
            // changed until it happened to be regenerated some other way.
            $this->pdfService->generate($receipt);
        }

        return $payment;
    }
}
