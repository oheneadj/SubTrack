<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Models\User;

test('total revenue and recent payments include directly-paid renewals, not just paid invoices', function () {
    $user = User::factory()->create();

    $invoicedClient = Client::create(['name' => 'Invoiced Co', 'email' => 'invoiced@test.test']);
    Invoice::create([
        'client_id' => $invoicedClient->id,
        'invoice_number' => 'INV-0001',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'tax_rate' => 0,
        'tax_amount' => 0,
        'subtotal' => 50000,
        'total_amount' => 50000, // $500.00
        'status' => InvoiceStatus::Paid,
    ]);

    $renewalOnlyClient = Client::create(['name' => 'Renewal Only Co', 'email' => 'renewalonly@test.test']);
    $subscription = Subscription::create([
        'client_id' => $renewalOnlyClient->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'renewalonly.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 5000,
        'status' => SubscriptionStatus::Active,
    ]);
    Renewal::create([
        'subscription_id' => $subscription->id,
        'due_date' => now(),
        'provider_cost_usd' => 1000,
        'client_cost_usd' => 5000, // $50.00, never invoiced
        'payment_status' => PaymentStatus::Renewed,
        'renewal_confirmed_date' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('finances.index'));

    $response->assertOk();
    $response->assertSee('550.00'); // $500 invoice + $50 renewal
    $response->assertSee('Renewal Only Co');
    $response->assertSee('Invoiced Co');
});

test('a renewal linked to a paid invoice is not double counted', function () {
    $user = User::factory()->create();

    $client = Client::create(['name' => 'Gateway Client', 'email' => 'gateway@test.test']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-0002',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'tax_rate' => 0,
        'tax_amount' => 0,
        'subtotal' => 5000,
        'total_amount' => 5000, // $50.00
        'status' => InvoiceStatus::Paid,
    ]);

    $subscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'gateway.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 5000,
        'status' => SubscriptionStatus::Active,
    ]);

    Renewal::create([
        'subscription_id' => $subscription->id,
        'invoice_id' => $invoice->id,
        'due_date' => now(),
        'provider_cost_usd' => 1000,
        'client_cost_usd' => 5000,
        'payment_status' => PaymentStatus::Paid,
        'payment_received_date' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('finances.index'));

    $response->assertOk();
    // $50 total, not $100 — the invoice-linked renewal must not be added on top of the invoice.
    $response->assertSee('50.00');
    $response->assertDontSee('100.00');
});
