<?php

declare(strict_types=1);

use App\Enums\SubscriptionRenewalType;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Subscription;

function makeMissedPaymentsSubscription(SubscriptionRenewalType $renewalType, int $daysOverdue): Subscription
{
    $client = Client::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider '.uniqid()]);

    return Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => $renewalType,
        'domain_name' => 'example-'.uniqid().'.com',
        'purchase_date' => now()->subYears(2),
        'expiry_date' => now()->subDays($daysOverdue),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Expired',
    ]);
}

test('is null when the subscription is not overdue', function () {
    $subscription = makeMissedPaymentsSubscription(SubscriptionRenewalType::RecurringMonthly, -5);

    expect($subscription->missed_payments_count)->toBeNull();
});

test('is null for a bare OneTime subscription with no fixed cycle', function () {
    $subscription = makeMissedPaymentsSubscription(SubscriptionRenewalType::OneTime, 45);

    expect($subscription->missed_payments_count)->toBeNull();
});

test('counts one missed payment for a monthly subscription within its first overdue cycle', function () {
    $subscription = makeMissedPaymentsSubscription(SubscriptionRenewalType::RecurringMonthly, 15);

    expect($subscription->missed_payments_count)->toBe(1);
});

test('counts multiple missed payments for a monthly subscription overdue several cycles', function () {
    $subscription = makeMissedPaymentsSubscription(SubscriptionRenewalType::RecurringMonthly, 65);

    expect($subscription->missed_payments_count)->toBe(3);
});

test('an annual subscription overdue by 65 days has only missed one payment', function () {
    $subscription = makeMissedPaymentsSubscription(SubscriptionRenewalType::RecurringAnnually, 65);

    expect($subscription->missed_payments_count)->toBe(1);
});
