<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Actions\GenerateInvoiceReceiptAction;
use App\Enums\PaymentRecordStatus;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Shared logic that all payment gateway implementations inherit.
 * Concrete gateways extend this and implement App\Contracts\PaymentGateway.
 */
abstract class AbstractGateway
{
    /**
     * Find a pending Payment record by the provider's payment/session/charge ID.
     * Returns null if not found — concrete gateways should handle that gracefully.
     */
    protected function findPaymentByGatewayId(string $gatewayPaymentId): ?Payment
    {
        return Payment::where('gateway_payment_id', $gatewayPaymentId)->first();
    }

    /**
     * Mark the payment as succeeded, recalculate the invoice's paid/partial
     * status from all succeeded payments (which also settles any linked
     * renewal once the invoice is fully paid — see
     * Invoice::recalculatePaymentStatus()), auto-generate a receipt for the
     * client since a gateway confirmation means no admin is present to do
     * it manually, and fire the InvoicePaid event.
     *
     * @param  array<string, mixed>|null  $gatewayResponse  Full raw provider response stored for audit.
     */
    protected function markInvoicePaid(Invoice $invoice, Payment $payment, ?array $gatewayResponse = null): void
    {
        // Idempotency — don't double-process if webhook fires twice.
        if ($payment->status === PaymentRecordStatus::Succeeded) {
            return;
        }

        $now = CarbonImmutable::now();

        $payment->update([
            'status' => PaymentRecordStatus::Succeeded,
            'paid_at' => $now,
            'gateway_response' => $gatewayResponse,
        ]);

        $invoice->recalculatePaymentStatus();
        $invoice = $invoice->fresh();

        if ($invoice->isPaid()) {
            $this->generateReceiptIfNeeded($invoice);
        }

        event(new InvoicePaid($invoice, $payment));
    }

    /**
     * Best-effort — a gateway-confirmed payment means the client paid
     * unattended, so unlike a manual payment (where the admin decides when
     * to generate one) we generate the receipt automatically here. Silently
     * skips if one already covers this amount (idempotency on a re-fired
     * webhook) or the invoice has no client to issue it to.
     */
    private function generateReceiptIfNeeded(Invoice $invoice): void
    {
        try {
            app(GenerateInvoiceReceiptAction::class)->execute($invoice);
        } catch (RuntimeException) {
            //
        }
    }
}
