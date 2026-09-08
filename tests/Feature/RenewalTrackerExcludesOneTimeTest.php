<?php

declare(strict_types=1);

use App\Livewire\Renewals\RenewalTracker;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('the renewal tracker excludes non-recurring subscriptions', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    $recurring = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'recurring.test',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    $oneTime = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Other',
        'renewal_type' => 'OneTime',
        'domain_name' => 'onetime.test',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 0,
        'status' => 'Active',
    ]);

    $ids = Livewire::actingAs($user)
        ->test(RenewalTracker::class)
        ->subscriptions->pluck('id');

    expect($ids)->toContain($recurring->id);
    expect($ids)->not->toContain($oneTime->id);
});
