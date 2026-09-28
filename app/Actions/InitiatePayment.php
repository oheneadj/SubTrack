<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentRecordStatus;
use App\Exceptions\InvalidPaymentAmountException;
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
     * @param  int|null  $amountCents  Custom amount to charge, in cents — defaults to
     *                                 the invoice's full remaining balance, so a client
     *                                 paying off the rest of a partially-paid invoice
     *                                 doesn't need to type anything.
     *
     * @throws InvoiceAlreadyPaidException if the invoice is already paid
     * @throws InvalidPaymentAmountException if the amount is invalid or exceeds the balance due
     */
    public function execute(Invoice $invoice, PaymentGateway $gateway, string $returnUrl, ?int $amountCents = null): string
    {
        if ($invoice->isPaid()) {
            throw new InvoiceAlreadyPaidException;
        }

        $amountCents ??= $invoice->balance_due;

        if ($amountCents <= 0 || $amountCents > $invoice->balance_due) {
            throw new InvalidPaymentAmountException;
        }

        // Create the pending record so the gateway can attach its session ID to it.
        Payment::create([
            'invoice_id' => $invoice->id,
            'gateway' => $gateway->slug(),
            'method' => $gateway->method(),
            'amount' => $amountCents,
            'currency' => 'usd',
            'status' => PaymentRecordStatus::Pending,
        ]);

        // Gateway fills in gateway_payment_id after creating the session.
        return $gateway->createCheckout($invoice, $returnUrl, $amountCents);
    }
}
