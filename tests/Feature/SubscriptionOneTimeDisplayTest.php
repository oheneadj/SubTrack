<?php

declare(strict_types=1);

use App\Actions\PrepareRenewalAction;
use App\Actions\RecordManualPaymentAction;
use App\Enums\PaymentStatus;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Livewire\Subscriptions\SubscriptionShow;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

function makeOneTimeTestSubscription(string $renewalType, int $daysUntilExpiry): Subscription
{
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $provider = Provider::create(['name' => 'Test Provider '.uniqid()]);

    return Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Hosting',
        'renewal_type' => $renewalType,
        'domain_name' => 'acme-'.uniqid().'.com',
        'purchase_date' => now()->subMonths(2),
        'expiry_date' => now()->addDays($daysUntilExpiry),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);
}

test('a bare OneTime subscription past its date has no missed payments count', function () {
    $subscription = makeOneTimeTestSubscription('OneTime', -30);

    expect($subscription->missed_payments_count)->toBeNull();
});

test('an OneTimeMonthly subscription past its term has no missed payments count', function () {
    $subscription = makeOneTimeTestSubscription('OneTimeMonthly', -45);

    expect($subscription->missed_payments_count)->toBeNull();
});

test('an OneTimeAnnually subscription past its term has no missed payments count', function () {
    $subscription = makeOneTimeTestSubscription('OneTimeAnnually', -400);

    expect($subscription->missed_payments_count)->toBeNull();
});

test('a RecurringMonthly subscription overdue still reports missed payments', function () {
    $subscription = makeOneTimeTestSubscription('RecurringMonthly', -45);

    expect($subscription->missed_payments_count)->not->toBeNull()
        ->and($subscription->missed_payments_count)->toBeGreaterThan(0);
});

test('cost cycle suffix reflects the actual billing cycle, not always /yr', function () {
    expect(makeOneTimeTestSubscription('RecurringMonthly', 30)->cost_cycle_suffix)->toBe('/mo');
    expect(makeOneTimeTestSubscription('OneTimeMonthly', 30)->cost_cycle_suffix)->toBe('/mo');
    expect(makeOneTimeTestSubscription('RecurringAnnually', 30)->cost_cycle_suffix)->toBe('/yr');
    expect(makeOneTimeTestSubscription('OneTimeAnnually', 30)->cost_cycle_suffix)->toBe('/yr');
    expect(makeOneTimeTestSubscription('OneTime', 30)->cost_cycle_suffix)->toBe('');
});

test('cost label distinguishes recurring renewal cost from a one-time purchase cost', function () {
    expect(makeOneTimeTestSubscription('RecurringAnnually', 30)->cost_label)->toBe('Renewal Cost');
    expect(makeOneTimeTestSubscription('OneTime', 30)->cost_label)->toBe('Purchase Cost');
});

test('the subscription show page does not show alarming red styling for a completed one-time purchase', function () {
    $subscription = makeOneTimeTestSubscription('OneTime', -30);

    Livewire::actingAs(User::factory()->create())
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertDontSee('Payments Missed')
        ->assertSee('Completed');
});

test('finance dashboard total costs and profit only count paid renewals, not ones still awaiting payment', function () {
    $subscription = makeOneTimeTestSubscription('RecurringAnnually', 5);

    $renewal = app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    $before = Livewire::actingAs(User::factory()->create())->test(FinanceDashboard::class);
    expect((float) $before->viewData('totalCosts'))->toBe(0.0)
        ->and((float) $before->viewData('profit'))->toBe(0.0);

    (new RecordManualPaymentAction)->execute($renewal->invoice, 1000);
    expect($renewal->fresh()->payment_status)->toBe(PaymentStatus::Paid);

    $after = Livewire::actingAs(User::factory()->create())->test(FinanceDashboard::class);
    expect((float) $after->viewData('totalCosts'))->toBe(10.0);
});
