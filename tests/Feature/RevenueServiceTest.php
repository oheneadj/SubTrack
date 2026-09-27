<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\OverviewDashboard;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RevenueService;
use Livewire\Livewire;

function makeDirectRenewal(int $clientCostUsd, $confirmedDate): Renewal
{
    $client = Client::factory()->create();
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'revsvc-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => $clientCostUsd,
        'status' => SubscriptionStatus::Active,
    ]);

    return Renewal::create([
        'subscription_id' => $subscription->id,
        'due_date' => now(),
        'provider_cost_usd' => 1000,
        'client_cost_usd' => $clientCostUsd,
        'payment_status' => PaymentStatus::Renewed,
        'renewal_confirmed_date' => $confirmedDate,
    ]);
}

test('totalRevenue includes directly-paid renewals alongside paid invoices', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-REV-0001',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 50000,
        'status' => InvoiceStatus::Paid,
    ]);

    makeDirectRenewal(5000, now());

    expect(app(RevenueService::class)->totalRevenue())->toBe(550.0);
});

test('currentMonthTotal and lastSixMonths attribute a direct renewal to the month it was confirmed', function () {
    makeDirectRenewal(10000, now()->startOfMonth()->addDays(2));

    $revenue = app(RevenueService::class);

    expect($revenue->currentMonthTotal())->toBe(100.0);

    $months = collect($revenue->lastSixMonths());
    $thisMonth = $months->last();
    expect($thisMonth['total'])->toBe(100.0);
});

test('comparisonData revenue includes direct renewals for the matching month', function () {
    makeDirectRenewal(20000, now());

    $data = collect(app(RevenueService::class)->comparisonData(1));

    expect($data->first()['revenue'])->toBe(200.0);
});

test('a renewal confirmed last month does not leak into this month\'s total', function () {
    makeDirectRenewal(30000, now()->subMonth());

    expect(app(RevenueService::class)->currentMonthTotal())->toBe(0.0);
    expect(app(RevenueService::class)->previousMonthTotal())->toBe(300.0);
});

test('OverviewDashboard and FinanceDashboard report the same total revenue', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-REV-0002',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 50000,
        'status' => InvoiceStatus::Paid,
    ]);
    makeDirectRenewal(5000, now());

    $user = User::factory()->create();

    $overviewTotal = Livewire::actingAs($user)->test(OverviewDashboard::class)->get('financeStats')['total_revenue'];
    $financeTotal = app(RevenueService::class)->totalRevenue();

    expect($overviewTotal)->toBe($financeTotal)->toBe(550.0);
});
