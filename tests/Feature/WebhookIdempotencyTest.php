<?php

declare(strict_types=1);

use App\Models\WebhookEvent;

function signedStripePayload(array $payload, string $secret): array
{
    $body = json_encode($payload);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);

    return [$body, "t={$timestamp},v1={$signature}"];
}

test('a redelivered webhook event is only processed once', function () {
    $secret = 'whsec_test_secret';
    config(['payments.stripe.webhook_secret' => $secret]);

    $payload = [
        'id' => 'evt_test_123',
        'type' => 'some.unhandled.event',
        'data' => ['object' => []],
    ];

    [$body, $signatureHeader] = signedStripePayload($payload, $secret);

    $headers = ['Stripe-Signature' => $signatureHeader];

    $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk();
    $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars($headers), $body)
        ->assertOk();

    expect(WebhookEvent::where('gateway', 'stripe')->where('event_id', 'evt_test_123')->count())->toBe(1);
});
