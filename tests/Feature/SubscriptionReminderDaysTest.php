<?php

declare(strict_types=1);

use App\Enums\SubscriptionRenewalType;
use App\Mail\SubscriptionReminderMail;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

function makeReminderTestSubscription(SubscriptionRenewalType $renewalType, int $daysUntilExpiry): Subscription
{
    $client = Client::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider']);

    return Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => $renewalType,
        'domain_name' => 'example-'.uniqid().'.com',
        'purchase_date' => now()->subMonths(2),
        'expiry_date' => now()->addDays($daysUntilExpiry),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);
}

test('applicableReminderDays keeps every configured day for an annual subscription', function () {
    $subscription = makeReminderTestSubscription(SubscriptionRenewalType::RecurringAnnually, 30);

    expect($subscription->applicableReminderDays([30, 14, 7]))->toBe([30, 14, 7]);
});

test('applicableReminderDays drops days that do not fit a monthly subscription\'s cycle', function () {
    $subscription = makeReminderTestSubscription(SubscriptionRenewalType::RecurringMonthly, 7);

    expect($subscription->applicableReminderDays([30, 14, 7]))->toBe([14, 7]);
});

test('applicableReminderDays keeps every configured day for bare OneTime (no fixed cycle)', function () {
    $subscription = makeReminderTestSubscription(SubscriptionRenewalType::OneTime, 30);

    expect($subscription->applicableReminderDays([30, 14, 7]))->toBe([30, 14, 7]);
});

test('the expiry check command reads the configured reminder_days setting instead of a hardcoded list', function () {
    Mail::fake();

    $annual = makeReminderTestSubscription(SubscriptionRenewalType::RecurringAnnually, 14);
    Setting::set('reminder_days', (string) $annual->days_until_expiry);

    Artisan::call('subtrack:check-expiries');

    Mail::assertQueued(SubscriptionReminderMail::class, fn ($mail) => $mail->subscription->is($annual));
});

test('a monthly subscription does not get a reminder at a day count that does not fit its cycle', function () {
    Mail::fake();

    $monthly = makeReminderTestSubscription(SubscriptionRenewalType::RecurringMonthly, 45);
    // Well past a monthly subscription's own ~30-day cycle, so it must be filtered out.
    Setting::set('reminder_days', (string) $monthly->days_until_expiry);

    Artisan::call('subtrack:check-expiries');

    Mail::assertNotQueued(SubscriptionReminderMail::class, fn ($mail) => $mail->subscription->is($monthly));
});

test('a monthly subscription still gets a reminder at a day count that fits its cycle', function () {
    Mail::fake();

    $monthly = makeReminderTestSubscription(SubscriptionRenewalType::RecurringMonthly, 7);
    Setting::set('reminder_days', (string) $monthly->days_until_expiry);

    Artisan::call('subtrack:check-expiries');

    Mail::assertQueued(SubscriptionReminderMail::class, fn ($mail) => $mail->subscription->is($monthly));
});
