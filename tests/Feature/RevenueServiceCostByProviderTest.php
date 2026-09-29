<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\FinanceDashboard;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RevenueService;
use Livewire\Livewire;

function makeProviderRenewal(string $providerName, int $providerCostCents, ?Provider $provider = null): Renewal
{
    $client = Client::factory()->create();
    $provider ??= Provider::create(['name' => $providerName.' '.uniqid()]);
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'provider-cost-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => $providerCostCents,
        'status' => SubscriptionStatus::Active,
    ]);

    return Renewal::create([
        'subscription_id' => $subscription->id,
        'due_date' => now(),
        'provider_cost_usd' => $providerCostCents,
        'client_cost_usd' => $providerCostCents * 2,
        'payment_status' => PaymentStatus::Renewed,
        'renewal_confirmed_date' => now(),
    ]);
}

test('costByProvider groups paid renewal costs by provider, sorted highest first', function () {
    $namecheap = Provider::create(['name' => 'Namecheap '.uniqid()]);
    makeProviderRenewal('Namecheap', 3000, $namecheap);
    makeProviderRenewal('AWS', 9000);
    makeProviderRenewal('Namecheap', 2000, $namecheap);

    $breakdown = app(RevenueService::class)->costByProvider();

    expect($breakdown)->toHaveCount(2)
        ->and($breakdown[0]['amount'])->toBe(90.0)
        ->and($breakdown[1]['amount'])->toBe(50.0);
});

test('costByProvider excludes renewals still Pending payment', function () {
    $client = Client::factory()->create();
    $provider = Provider::create(['name' => 'Pending Test '.uniqid()]);
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'provider-pending-'.uniqid().'.test',
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
        'client_cost_usd' => 10000,
        'payment_status' => PaymentStatus::Pending,
    ]);

    expect(app(RevenueService::class)->costByProvider())->toBe([]);
});

test('Finance dashboard renders the provider cost breakdown', function () {
    makeProviderRenewal('DigitalOcean', 4500);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(FinanceDashboard::class);

    $response->assertSee('Provider Costs Breakdown')
        ->assertSee('DigitalOcean', escape: false)
        ->assertSee('$45.00');
});
