<?php

declare(strict_types=1);

use App\Livewire\Subscriptions\SubscriptionForm;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('recurring monthly auto-sets expiry to one month after purchase', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    Livewire::actingAs($user)
        ->test(SubscriptionForm::class)
        ->set('client_id', $client->id)
        ->set('provider_id', $provider->id)
        ->set('renewal_type', 'RecurringMonthly')
        ->set('purchase_date', '2026-01-15')
        ->set('domain_name', 'example.com')
        ->set('purchase_cost_usd', 10)
        ->set('renewal_cost_usd', 10)
        ->call('save');

    $subscription = Subscription::where('domain_name', 'example.com')->firstOrFail();

    expect($subscription->expiry_date->format('Y-m-d'))->toBe('2026-02-15');
});

test('bare one-time leaves a manually entered expiry date untouched', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    Livewire::actingAs($user)
        ->test(SubscriptionForm::class)
        ->set('client_id', $client->id)
        ->set('provider_id', $provider->id)
        ->set('renewal_type', 'OneTime')
        ->set('purchase_date', '2026-01-15')
        ->set('expiry_date', '2030-12-31')
        ->set('domain_name', 'onceoff.com')
        ->set('purchase_cost_usd', 10)
        ->set('renewal_cost_usd', 0)
        ->call('save');

    $subscription = Subscription::where('domain_name', 'onceoff.com')->firstOrFail();

    expect($subscription->expiry_date->format('Y-m-d'))->toBe('2030-12-31');
});

test('a subscription created without a project is attached to the client\'s Unrelated project', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    Livewire::actingAs($user)
        ->test(SubscriptionForm::class)
        ->set('client_id', $client->id)
        ->set('provider_id', $provider->id)
        ->set('renewal_type', 'RecurringAnnually')
        ->set('purchase_date', '2026-01-15')
        ->set('domain_name', 'noproject.com')
        ->set('purchase_cost_usd', 10)
        ->set('renewal_cost_usd', 10)
        ->call('save');

    $subscription = Subscription::where('domain_name', 'noproject.com')->firstOrFail();

    expect($subscription->project?->project_name)->toBe('Unrelated');
});
