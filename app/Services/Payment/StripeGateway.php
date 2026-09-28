<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentRecordStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Stripe payment gateway — handles card payments via Stripe Checkout.
 *
 * Requires STRIPE_KEY, STRIPE_SECRET, and STRIPE_WEBHOOK_SECRET in .env.
 */
class StripeGateway extends AbstractGateway implements PaymentGateway
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('payments.stripe.secret'));
    }

    public function label(): string
    {
        return 'Pay with Card';
    }

    public function slug(): string
    {
        return 'stripe';
    }

    public function method(): string
    {
        return 'card';
    }

    /**
     * Create a Stripe Checkout Session and return its hosted URL.
     * The invoice's line items are mapped to Stripe's price_data format
     * when the full balance is being charged; a partial amount is charged
     * as a single custom line item instead, since the itemized breakdown
     * no longer sums to what's actually being collected.
     */
    public function createCheckout(Invoice $invoice, string $returnUrl, int $amountCents): string
    {
        $invoice->loadMissing('items', 'client');

        if ($amountCents === $invoice->balance_due && $invoice->balance_due === $invoice->total_amount) {
            $lineItems = $invoice->items->map(fn ($item) => [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $item->unit_price, // already in cents
                    'product_data' => [
                        'name' => $item->description,
                        'description' => $item->period ?? null,
                    ],
                ],
                'quantity' => max(1, (int) $item->quantity),
            ])->values()->all();
        } else {
            $lineItems = [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => "Payment for Invoice {$invoice->invoice_number}",
                    ],
                ],
                'quantity' => 1,
            ]];
        }

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'customer_email' => $invoice->client->email,
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $returnUrl.'?payment=success',
            'cancel_url' => $returnUrl.'?payment=cancelled',
            'metadata' => [
                'invoice_ulid' => $invoice->ulid,
            ],
        ]);

        // Store the session ID on the pending Payment so the webhook can look it up.
        Payment::where('invoice_id', $invoice->id)
            ->where('status', PaymentRecordStatus::Pending)
            ->where('gateway', $this->slug())
            ->latest()
            ->first()
            ?->update(['gateway_payment_id' => $session->id]);

        return $session->url;
    }

    /**
     * Verify the Stripe webhook signature using the raw request body.
     * Throws SignatureVerificationException on failure.
     */
    public function verifyWebhook(Request $request): void
    {
        Webhook::constructEvent(
            $request->getContent(),
            $request->header('Stripe-Signature', ''),
            config('payments.stripe.webhook_secret'),
        );
    }

    /**
     * Handle a verified Stripe webhook.
     * Only acts on checkout.session.completed — all other events are ignored.
     */
    public function handleWebhook(Request $request): void
    {
        $payload = json_decode($request->getContent(), true);
        $event = $payload['type'] ?? '';

        if ($event !== 'checkout.session.completed') {
            return;
        }

        $sessionId = $payload['data']['object']['id'] ?? null;
        $invoiceUlid = $payload['data']['object']['metadata']['invoice_ulid'] ?? null;

        if (! $sessionId || ! $invoiceUlid) {
            return;
        }

        $payment = $this->findPaymentByGatewayId($sessionId);
        if (! $payment) {
            return;
        }

        $this->markInvoicePaid(
            $payment->invoice,
            $payment,
            $payload['data']['object'],
        );
    }

    /** Stripe's `id` field on the event envelope (e.g. "evt_..."), unique per event delivery. */
    public function webhookEventId(Request $request): ?string
    {
        $payload = json_decode($request->getContent(), true);

        return $payload['id'] ?? null;
    }

    /**
     * Poll Stripe directly for this payment's checkout session status.
     * Covers the case where the checkout.session.completed webhook never arrived.
     */
    public function pollStatus(Payment $payment): void
    {
        if (! $payment->gateway_payment_id) {
            return;
        }

        $session = $this->stripe->checkout->sessions->retrieve($payment->gateway_payment_id);

        if ($session->payment_status === 'paid') {
            $this->markInvoicePaid($payment->invoice, $payment, $session->toArray());
        }
    }
}
