<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Providers\ProviderIndex;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Renewal;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

function createProviderWithCost(string $name, int $providerCostCents): Provider
{
    $provider = Provider::create(['name' => $name]);
    $client = Client::factory()->create();
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'provider-index-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => $providerCostCents,
        'status' => SubscriptionStatus::Active,
    ]);
    Renewal::create([
        'subscription_id' => $subscription->id,
        'due_date' => now(),
        'provider_cost_usd' => $providerCostCents,
        'client_cost_usd' => $providerCostCents * 2,
        'payment_status' => PaymentStatus::Renewed,
        'renewal_confirmed_date' => now(),
    ]);

    return $provider;
}

test('provider index shows total cost paid to each provider', function () {
    $provider = createProviderWithCost('Cost Column Test', 4500);

    // Scope past the ~15+ default providers a migration seeds — without
    // this, pagination can push a freshly created fixture off page 1.
    $response = Livewire::actingAs(User::factory()->create())
        ->test(ProviderIndex::class)
        ->set('search', 'Cost Column Test');

    $found = $response->viewData('providers')->firstWhere('id', $provider->id);
    expect($found)->not->toBeNull()
        ->and((float) $found->total_cost_cents / 100)->toBe(45.0);
});

test('provider index excludes renewals still Pending payment from total cost', function () {
    $provider = Provider::create(['name' => 'Pending Cost Test']);
    $client = Client::factory()->create();
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

    $response = Livewire::actingAs(User::factory()->create())
        ->test(ProviderIndex::class)
        ->set('search', 'Pending Cost Test');

    $found = $response->viewData('providers')->firstWhere('id', $provider->id);
    expect($found)->not->toBeNull()
        ->and((float) $found->total_cost_cents)->toBe(0.0);
});

test('provider index can be sorted by total cost', function () {
    // A migration seeds ~15+ default providers with no renewal cost of
    // their own — scoping by search keeps both fixtures on page 1 and
    // isolates the comparison to just the two of them.
    $low = createProviderWithCost('Sortable Cost Provider Low', 1000);
    $high = createProviderWithCost('Sortable Cost Provider High', 9000);

    $response = Livewire::actingAs(User::factory()->create())
        ->test(ProviderIndex::class)
        ->set('search', 'Sortable Cost Provider');

    $response->call('sortBy', 'total_cost_cents');
    $ascendingIds = $response->viewData('providers')->pluck('id')->values();
    expect($ascendingIds->search($low->id))->toBeLessThan($ascendingIds->search($high->id));

    $response->call('sortBy', 'total_cost_cents');
    $descendingIds = $response->viewData('providers')->pluck('id')->values();
    expect($descendingIds->search($high->id))->toBeLessThan($descendingIds->search($low->id));
});
