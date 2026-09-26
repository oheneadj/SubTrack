<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonImmutable;

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
     * Mark the payment as succeeded, mark the invoice as paid,
     * update any linked renewal, and fire the InvoicePaid event.
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

        $invoice->update(['status' => InvoiceStatus::Paid]);

        // Update the renewal linked to this invoice, if any.
        $renewal = $invoice->renewals()->first();
        if ($renewal) {
            $renewal->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_received_date' => $now->toDateString(),
            ]);
        }

        event(new InvoicePaid($invoice, $payment));
    }
}
