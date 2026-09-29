<?php

declare(strict_types=1);

use App\Livewire\Subscriptions\SubscriptionIndex;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

function makeTrashTestSubscription(Client $client, Provider $provider, array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'example-'.uniqid().'.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ], $overrides));
}

test('the trash toggle shows only deleted subscriptions, and restore brings one back', function () {
    $client = Client::factory()->create();
    $provider = Provider::create(['name' => 'Trash Test Provider '.uniqid()]);

    $active = makeTrashTestSubscription($client, $provider, ['domain_name' => 'active-sub.test']);
    $deleted = makeTrashTestSubscription($client, $provider, ['domain_name' => 'deleted-sub.test']);
    $deleted->delete();

    $response = Livewire::actingAs(User::factory()->create())->test(SubscriptionIndex::class);

    // Default view: only the active subscription, not the deleted one.
    $response->assertSee('active-sub.test')->assertDontSee('deleted-sub.test');

    $response->call('toggleTrash');

    // Trash view: only the deleted subscription, not the active one.
    $response->assertSee('deleted-sub.test')->assertDontSee('active-sub.test');

    $response->call('restore', $deleted->ulid);

    expect(Subscription::find($deleted->id))->not->toBeNull();
});

test('bulk-select and Add Subscription are hidden while viewing the trash', function () {
    $client = Client::factory()->create();
    $provider = Provider::create(['name' => 'Trash Hide Test '.uniqid()]);
    $deleted = makeTrashTestSubscription($client, $provider, ['domain_name' => 'trash-hide.test']);
    $deleted->delete();

    $response = Livewire::actingAs(User::factory()->create())
        ->test(SubscriptionIndex::class)
        ->call('toggleTrash');

    $response->assertDontSee('Add Subscription')
        ->assertDontSee('checkbox checkbox-sm checkbox-primary', false);
});
