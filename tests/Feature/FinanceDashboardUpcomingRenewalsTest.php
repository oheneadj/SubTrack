<?php

declare(strict_types=1);

use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('upcoming renewals shows the correctly formatted cost, not raw cents', function () {
    $client = Client::factory()->create();
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'upcoming-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(10),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 6900, // $69.00 in cents
        'status' => SubscriptionStatus::Active,
    ]);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    // The bug: the card used to render number_format($sub->renewal_cost_usd, 2)
    // with no /100 conversion at all, showing $69.00 as $6,900.00.
    $response->assertSee('$69.00')
        ->assertDontSee('$6,900.00');
});

test('upcoming renewals shows both the provider cost and the client bill', function () {
    $client = Client::factory()->create();
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'marked-up-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(10),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 5000, // $50.00 cost
        'markup_percentage' => 20, // $60.00 billed to the client
        'status' => SubscriptionStatus::Active,
    ]);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    $response->assertSee('$60.00')->assertSee('$50.00 cost');
});

test('upcoming renewals excludes one-time subscriptions', function () {
    $client = Client::factory()->create();
    Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::OneTime,
        'domain_name' => 'onetime-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(5),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 5000,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    expect($response->viewData('upcomingRenewals'))->toHaveCount(0);
});
