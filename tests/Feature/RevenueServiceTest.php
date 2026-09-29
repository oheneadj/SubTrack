<?php

declare(strict_types=1);

use App\Actions\PrepareRenewalAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\OverviewDashboard;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
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

test('OverviewDashboard and FinanceDashboard report the same outstanding revenue, MRR, and provider costs', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-REV-OUT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 12000,
        'status' => InvoiceStatus::Sent,
    ]);
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'mrr-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 12000,
        'status' => SubscriptionStatus::Active,
    ]);

    $user = User::factory()->create();
    $stats = Livewire::actingAs($user)->test(OverviewDashboard::class)->get('financeStats');
    $revenue = app(RevenueService::class);

    expect($stats['outstanding'])->toBe($revenue->outstandingRevenue())
        ->and($stats['mrr'])->toBe($revenue->estimatedMonthlyRecurringRevenue())
        ->and($stats['costs'])->toBe($revenue->totalProviderCosts());
});

test('totalProviderCosts excludes a renewal still Pending payment', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'P']);
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'pending-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(5),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    // Renewal created Pending — the moment "Start Renewal" raises its
    // invoice, before any payment has actually been collected.
    app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    expect(app(RevenueService::class)->totalProviderCosts())->toBe(0.0);
});

test('outstandingRevenue includes the remaining balance of a partially paid invoice', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-OUT-PARTIAL-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 50000,
        'amount_paid' => 20000,
        'status' => InvoiceStatus::PartiallyPaid,
    ]);
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-OUT-SENT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);

    // Previously: only fully-unpaid Sent/Overdue invoices counted, and by
    // their full total_amount — a Partially Paid invoice's remaining $300
    // balance was invisible everywhere. Expected: $300 (remaining on the
    // partial) + $100 (the untouched Sent invoice) = $400.
    expect(app(RevenueService::class)->outstandingRevenue())->toBe(400.0);
});

test('outstandingRevenue excludes a fully Draft invoice not yet sent to the client', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-OUT-DRAFT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 99999,
        'status' => InvoiceStatus::Draft,
    ]);

    expect(app(RevenueService::class)->outstandingRevenue())->toBe(0.0);
});

test('draftInvoiceTotal sums invoices not yet sent, separately from outstanding revenue', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-DRAFT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 25000,
        'status' => InvoiceStatus::Draft,
    ]);
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-SENT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);

    $revenue = app(RevenueService::class);

    expect($revenue->draftInvoiceTotal())->toBe(250.0)
        ->and($revenue->outstandingRevenue())->toBe(100.0);
});

test('comparisonData expenses are in dollars, not 100x too large in cents', function () {
    $client = Client::factory()->create();
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'expense-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 6900, // $69.00 in cents
        'status' => SubscriptionStatus::Active,
    ]);

    $data = app(RevenueService::class)->comparisonData(1);

    // $69.00/yr / 12 months = $5.75/mo — not $575 (the 100x-too-large
    // bug this reproduces: dividing cents by 12 without also by 100).
    expect($data[0]['expenses'])->toBe(5.75);
});
