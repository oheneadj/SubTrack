<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Contract every payment gateway must fulfill.
 *
 * To add a new gateway: implement this interface, extend AbstractGateway,
 * then add the class FQCN to config/payments.php gateways array.
 * No other files need to change.
 */
interface PaymentGateway
{
    /** Human-readable label shown on the payment page, e.g. "Pay with Card". */
    public function label(): string;

    /** Short slug used in URLs and stored in DB, e.g. "stripe". Must be unique per gateway. */
    public function slug(): string;

    /** Payment method type for display, e.g. "card", "mobile_money", "crypto". */
    public function method(): string;

    /**
     * Create a checkout session with the provider and return the redirect URL.
     * The client will be redirected to this URL to complete payment.
     *
     * @param  int  $amountCents  The amount to charge, in cents — may be less than
     *                            the invoice's full total_amount for a partial payment.
     */
    public function createCheckout(Invoice $invoice, string $returnUrl, int $amountCents): string;

    /**
     * Verify the incoming webhook request signature.
     * Should throw an exception if the signature is invalid.
     */
    public function verifyWebhook(Request $request): void;

    /**
     * Process a verified webhook payload.
     * Responsible for updating Payment, Invoice, and Renewal records.
     */
    public function handleWebhook(Request $request): void;

    /**
     * Extract the provider's unique event ID from a verified webhook payload,
     * used to deduplicate re-delivered events before handleWebhook() runs.
     * Return null if the payload carries no identifiable event ID.
     */
    public function webhookEventId(Request $request): ?string;

    /**
     * Actively check the provider for this payment's current status.
     * Called by the polling fallback for payments still pending past a
     * grace period, in case the webhook never arrived. Should be a no-op
     * if the provider still reports the payment as pending.
     */
    public function pollStatus(Payment $payment): void;
}
