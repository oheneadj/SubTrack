<?php

declare(strict_types=1);

use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RevenueService;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

test('recentChurn counts a subscription auto-cancelled within the window and estimates lost monthly revenue', function () {
    Setting::set('grace_period_days', '14');
    Setting::set('reminder_days', '30,14,7');

    $client = Client::factory()->create();
    // 20 days overdue, 14-day grace period lapsed — auto-cancelled by the
    // command, same setup as OverduePaymentPenaltyTest's cancellation case.
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => 'Domain',
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'churn-'.uniqid().'.test',
        'purchase_date' => now()->subYears(2),
        'expiry_date' => now()->subDays(20),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 12000, // $120/yr = $10/mo
        'status' => SubscriptionStatus::Expired,
    ]);

    Artisan::call('subtrack:check-expiries');

    expect(DashboardActivityLog::where('event_type', 'subscription.auto_cancelled')->count())->toBe(1);

    $churn = app(RevenueService::class)->recentChurn(90);

    expect($churn['count'])->toBe(1)
        ->and($churn['lostMonthlyRevenue'])->toBe(10.0);
});

test('recentChurn ignores cancellations outside the requested window', function () {
    Setting::set('grace_period_days', '14');
    Setting::set('reminder_days', '30,14,7');

    $client = Client::factory()->create();
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => 'Domain',
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'churn-old-'.uniqid().'.test',
        'purchase_date' => now()->subYears(2),
        'expiry_date' => now()->subDays(20),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 12000,
        'status' => SubscriptionStatus::Expired,
    ]);

    Artisan::call('subtrack:check-expiries');

    // Backdate the activity log entry itself, not the subscription — the
    // whole point of using the log is that it carries its own reliable
    // timestamp independent of the subscription row.
    DashboardActivityLog::where('event_type', 'subscription.auto_cancelled')
        ->update(['created_at' => now()->subDays(120)]);

    expect(app(RevenueService::class)->recentChurn(90)['count'])->toBe(0);
});

test('Finance dashboard shows a churn alert only when something has actually churned', function () {
    $response = Livewire::actingAs(User::factory()->create())->test(FinanceDashboard::class);

    $response->assertDontSee('auto-cancelled in the last 90 days');

    Setting::set('grace_period_days', '14');
    Setting::set('reminder_days', '30,14,7');
    $client = Client::factory()->create();
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => 'Domain',
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'churn-alert-'.uniqid().'.test',
        'purchase_date' => now()->subYears(2),
        'expiry_date' => now()->subDays(20),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 12000,
        'status' => SubscriptionStatus::Expired,
    ]);
    Artisan::call('subtrack:check-expiries');

    $response = Livewire::actingAs(User::factory()->create())->test(FinanceDashboard::class);

    $response->assertSee('auto-cancelled in the last 90 days');
});
