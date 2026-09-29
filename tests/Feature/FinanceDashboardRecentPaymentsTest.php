<?php

declare(strict_types=1);

use App\Actions\RecordManualPaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('recent payments includes a partially paid invoice, showing only the amount actually received', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-FIN-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);
    (new RecordManualPaymentAction)->execute($invoice, 4000);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    $recentPayments = $response->viewData('recentPayments');
    $entry = $recentPayments->firstWhere('reference', $invoice->invoice_number);

    expect($entry)->not->toBeNull()
        ->and((float) $entry->amount)->toBe(40.0);
});

test('Finance dashboard displays realized profit, previously computed but never shown', function () {
    $client = Client::factory()->create();
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'profit-view-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(5),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);
    Renewal::create([
        'subscription_id' => $subscription->id,
        'due_date' => now(),
        'provider_cost_usd' => 4000,
        'client_cost_usd' => 10000,
        'payment_status' => PaymentStatus::Renewed,
        'renewal_confirmed_date' => now(),
    ]);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    $response->assertSee('Profit (Realized)')->assertSee('$60.00');
});

test('Finance dashboard stat cards show compact K formatting for large amounts', function () {
    $client = Client::factory()->create();
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-COMPACT-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 150000, // $1,500.00 paid
        'amount_paid' => 150000,
        'status' => InvoiceStatus::Paid,
    ]);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    // The stat card compacts to $1.5K; the Recent Payments list below it
    // still shows the precise $1,500.00 for that same payment — only the
    // summary cards use compact formatting, not itemized lists.
    $response->assertSee('$1.5K');
});
