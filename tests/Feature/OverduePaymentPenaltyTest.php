<?php

declare(strict_types=1);

use App\Enums\ActivityEventType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Renewals\RenewalTracker;
use App\Livewire\Subscriptions\SubscriptionShow;
use App\Mail\SubscriptionReminderMail;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function makePenaltyTestSubscription(int $daysOverdue, SubscriptionStatus $status = SubscriptionStatus::Expired): Subscription
{
    $client = Client::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider '.uniqid()]);

    return Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => SubscriptionRenewalType::RecurringMonthly,
        'domain_name' => 'example-'.uniqid().'.com',
        'purchase_date' => now()->subYears(2),
        'expiry_date' => now()->subDays($daysOverdue),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 10000,
        'status' => $status,
    ]);
}

test('stated penalty is null when not overdue', function () {
    Setting::set('penalty_percentage', '5');
    $subscription = makePenaltyTestSubscription(-5, SubscriptionStatus::Active);

    expect($subscription->stated_penalty_percentage)->toBeNull();
    expect($subscription->stated_penalty_amount)->toBeNull();
});

test('stated penalty scales with missed payment cycles', function () {
    Setting::set('penalty_percentage', '5');
    // 65 days overdue on a monthly cycle = 3 missed payments (floor(65/30)+1).
    $subscription = makePenaltyTestSubscription(65);

    expect($subscription->missed_payments_count)->toBe(3);
    expect($subscription->stated_penalty_percentage)->toBe(15.0);
    // $100.00 renewal cost * 15% = $15.00 = 1500 cents.
    expect($subscription->stated_penalty_amount)->toBe(1500);
    expect($subscription->formatted_stated_penalty_amount)->toBe('$15.00');
});

test('stated penalty is null when no percentage is configured', function () {
    Setting::set('penalty_percentage', '0');
    $subscription = makePenaltyTestSubscription(40);

    expect($subscription->stated_penalty_amount)->toBeNull();
});

test('grace period deadline is null when not overdue or not configured', function () {
    Setting::set('grace_period_days', '14');
    $notOverdue = makePenaltyTestSubscription(-5, SubscriptionStatus::Active);
    expect($notOverdue->grace_period_deadline)->toBeNull();

    Setting::set('grace_period_days', '0');
    $overdueNoGrace = makePenaltyTestSubscription(10);
    expect($overdueNoGrace->grace_period_deadline)->toBeNull();
});

test('grace period deadline is the expiry date plus the configured days', function () {
    Setting::set('grace_period_days', '14');
    $subscription = makePenaltyTestSubscription(5);

    expect($subscription->grace_period_deadline->toDateString())
        ->toBe($subscription->expiry_date->addDays(14)->toDateString());
});

test('the reminder email states the penalty and grace period once overdue', function () {
    Setting::set('penalty_percentage', '5');
    Setting::set('grace_period_days', '14');
    $subscription = makePenaltyTestSubscription(40);

    $html = (new SubscriptionReminderMail($subscription))->render();

    expect($html)->toContain('Payment Overdue Notice');
    expect($html)->toContain('2 renewal payments');
    expect($html)->toContain('You must renew by');
    expect($html)->toContain('no cost or liability');
});

test('the reminder email does not mention penalties before expiry', function () {
    Setting::set('penalty_percentage', '5');
    $subscription = makePenaltyTestSubscription(-10, SubscriptionStatus::Active);

    $html = (new SubscriptionReminderMail($subscription))->render();

    expect($html)->not->toContain('Payment Overdue Notice');
});

test('subscription-show displays the stated penalty amount once accrued', function () {
    Setting::set('penalty_percentage', '5');
    $subscription = makePenaltyTestSubscription(65);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(SubscriptionShow::class, ['subscription' => $subscription]);

    // Previously computed and never shown anywhere but a reminder email.
    $response->assertSee('$15.00')->assertSee('Late Penalty Owed');
});

test('subscription-show does not show a penalty card when none has accrued', function () {
    Setting::set('penalty_percentage', '5');
    $subscription = makePenaltyTestSubscription(-5, SubscriptionStatus::Active);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(SubscriptionShow::class, ['subscription' => $subscription]);

    $response->assertDontSee('Late Penalty Owed');
});

test('renewal tracker shows the penalty amount next to missed payments', function () {
    Setting::set('penalty_percentage', '5');
    $subscription = makePenaltyTestSubscription(65);

    $response = Livewire::actingAs(User::factory()->create())->test(RenewalTracker::class);

    $response->assertSee('3 payments missed')->assertSee('$15.00 penalty');
});

test('a subscription is auto-cancelled once the grace period lapses', function () {
    Mail::fake();
    Setting::set('grace_period_days', '14');
    Setting::set('reminder_days', '30,14,7');

    // 20 days overdue, 14-day grace period already lapsed.
    $cancelled = makePenaltyTestSubscription(20);
    // 5 days overdue, still within the 14-day grace period.
    $stillGrace = makePenaltyTestSubscription(5);

    Artisan::call('subtrack:check-expiries');

    expect($cancelled->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
    expect($stillGrace->fresh()->status)->toBe(SubscriptionStatus::Expired);

    expect(DashboardActivityLog::where('event_type', ActivityEventType::SubscriptionAutoCancelled)->count())->toBe(1);
});
