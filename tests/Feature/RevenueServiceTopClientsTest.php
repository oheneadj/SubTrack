<?php

declare(strict_types=1);

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
use App\Services\RevenueService;
use Livewire\Livewire;

test('topClientsByRevenue combines invoice payments and direct renewal payments per client', function () {
    $bigClient = Client::create(['name' => 'Big Client '.uniqid(), 'email' => 'big-'.uniqid().'@test.test']);
    Invoice::create([
        'client_id' => $bigClient->id,
        'invoice_number' => 'INV-TOP-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 50000,
        'amount_paid' => 50000,
        'status' => InvoiceStatus::Paid,
    ]);

    $smallClient = Client::create(['name' => 'Small Client '.uniqid(), 'email' => 'small-'.uniqid().'@test.test']);
    Invoice::create([
        'client_id' => $smallClient->id,
        'invoice_number' => 'INV-TOP-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'amount_paid' => 10000,
        'status' => InvoiceStatus::Paid,
    ]);

    $breakdown = app(RevenueService::class)->topClientsByRevenue();

    expect($breakdown[0]['name'])->toBe($bigClient->name)
        ->and($breakdown[0]['amount'])->toBe(500.0)
        ->and($breakdown[1]['name'])->toBe($smallClient->name)
        ->and($breakdown[1]['amount'])->toBe(100.0);
});

test('topClientsByRevenue attributes a direct renewal to the project\'s client, not just the subscription\'s own client_id', function () {
    $client = Client::create(['name' => 'Project Owner '.uniqid(), 'email' => 'owner-'.uniqid().'@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Owner Project']);
    $subscription = Subscription::create([
        'project_id' => $project->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'top-client-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 5000,
        'status' => SubscriptionStatus::Active,
    ]);
    Renewal::create([
        'subscription_id' => $subscription->id,
        'due_date' => now(),
        'provider_cost_usd' => 5000,
        'client_cost_usd' => 7500,
        'payment_status' => PaymentStatus::Renewed,
        'renewal_confirmed_date' => now(),
    ]);

    $breakdown = app(RevenueService::class)->topClientsByRevenue();

    expect($breakdown)->toHaveCount(1)
        ->and($breakdown[0]['name'])->toBe($client->name)
        ->and($breakdown[0]['amount'])->toBe(75.0);
});

test('topClientsByRevenue respects the limit parameter', function () {
    foreach (range(1, 3) as $i) {
        $client = Client::create(['name' => "Client {$i} ".uniqid(), 'email' => "client{$i}-".uniqid().'@test.test']);
        Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-LIMIT-'.uniqid(),
            'issued_date' => now(),
            'due_date' => now()->addDays(14),
            'total_amount' => 1000 * $i,
            'amount_paid' => 1000 * $i,
            'status' => InvoiceStatus::Paid,
        ]);
    }

    expect(app(RevenueService::class)->topClientsByRevenue(2))->toHaveCount(2);
});

test('Finance dashboard renders the top clients breakdown', function () {
    $client = Client::create(['name' => 'Rendered Client '.uniqid(), 'email' => 'rendered-'.uniqid().'@test.test']);
    Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-RENDER-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 20000,
        'amount_paid' => 20000,
        'status' => InvoiceStatus::Paid,
    ]);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    $response->assertSee('Top Clients by Revenue')
        ->assertSee($client->name, escape: false)
        ->assertSee('$200.00');
});
