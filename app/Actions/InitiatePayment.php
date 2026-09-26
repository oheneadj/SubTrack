<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentRecordStatus;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;
use App\Models\Payment;

/**
 * Creates a pending Payment record and kicks off a checkout session with the gateway.
 * Returns the provider's redirect URL to send the client to for payment.
 */
class InitiatePayment
{
    /**
     * @throws InvoiceAlreadyPaidException if the invoice is already paid
     */
    public function execute(Invoice $invoice, PaymentGateway $gateway, string $returnUrl): string
    {
        if ($invoice->isPaid()) {
            throw new InvoiceAlreadyPaidException;
        }

        // Create the pending record so the gateway can attach its session ID to it.
        Payment::create([
            'invoice_id' => $invoice->id,
            'gateway' => $gateway->slug(),
            'method' => $gateway->method(),
            'amount' => $invoice->total_amount,
            'currency' => 'usd',
            'status' => PaymentRecordStatus::Pending,
        ]);

        // Gateway fills in gateway_payment_id after creating the session.
        return $gateway->createCheckout($invoice, $returnUrl);
    }
}
