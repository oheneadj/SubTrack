<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Exceptions\PaymentNotVoidableException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
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
        $invoice = $payment->invoice()->first();
        $invoice->recalculatePaymentStatus();

        $this->invalidateReceiptsExceedingAmountPaid($invoice, $reason);

        return $payment;
    }

    /**
     * Any receipt that states more than the invoice's now-lower amount
     * paid no longer reflects reality — flag it rather than let it keep
     * looking like a valid proof of payment. Looped (not a bulk update)
     * so ReceiptObserver still fires and logs each one.
     */
    private function invalidateReceiptsExceedingAmountPaid(Invoice $invoice, ?string $reason): void
    {
        $affectedReceipts = $invoice->receipts()
            ->whereNull('invalidated_at')
            ->where('amount_usd', '>', $invoice->amount_paid)
            ->get();

        foreach ($affectedReceipts as $receipt) {
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
    }
}
