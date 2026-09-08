<?php

declare(strict_types=1);

use App\Livewire\Subscriptions\SubscriptionIndex;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

function makeSubscription(Client $client, Provider $provider, array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'example.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ], $overrides));
}

test('search matches by client name and project name', function () {
    $user = User::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider']);

    $clientA = Client::create(['name' => 'Acme Corp', 'email' => 'acme@test.test']);
    $clientB = Client::create(['name' => 'Beta Inc', 'email' => 'beta@test.test']);
    $project = Project::create(['client_id' => $clientB->id, 'project_name' => 'Rocket Launch']);

    $subA = makeSubscription($clientA, $provider, ['domain_name' => 'acme.com']);
    $subB = makeSubscription($clientB, $provider, ['project_id' => $project->id, 'domain_name' => 'beta.com']);

    $component = Livewire::actingAs($user)->test(SubscriptionIndex::class)->set('search', 'Acme Corp');
    expect($component->subscriptions->pluck('id'))->toContain($subA->id)->not->toContain($subB->id);

    $component = Livewire::actingAs($user)->test(SubscriptionIndex::class)->set('search', 'Rocket Launch');
    expect($component->subscriptions->pluck('id'))->toContain($subB->id)->not->toContain($subA->id);
});

test('client filter narrows results to that client only', function () {
    $user = User::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider']);
    $clientA = Client::create(['name' => 'Acme Corp', 'email' => 'acme@test.test']);
    $clientB = Client::create(['name' => 'Beta Inc', 'email' => 'beta@test.test']);

    $subA = makeSubscription($clientA, $provider);
    $subB = makeSubscription($clientB, $provider);

    $component = Livewire::actingAs($user)->test(SubscriptionIndex::class)->set('filterClientId', $clientA->id);

    expect($component->subscriptions->pluck('id'))->toContain($subA->id)->not->toContain($subB->id);
});

test('sorting by client name orders subscriptions by their effective client, ascending or descending', function () {
    $user = User::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider']);

    $clientA = Client::create(['name' => 'Acme Corp', 'email' => 'acme@test.test']);
    $clientB = Client::create(['name' => 'Beta Inc', 'email' => 'beta@test.test']);
    $project = Project::create(['client_id' => $clientB->id, 'project_name' => 'Rocket Launch']);

    // subA links directly to a client, subB links via a project — both must sort correctly.
    $subA = makeSubscription($clientA, $provider, ['domain_name' => 'acme.com']);
    $subB = makeSubscription($clientB, $provider, ['client_id' => null, 'project_id' => $project->id, 'domain_name' => 'beta.com']);

    $component = Livewire::actingAs($user)->test(SubscriptionIndex::class)->call('sortBy', 'client_name');
    expect($component->subscriptions->pluck('id')->toArray())->toBe([$subA->id, $subB->id]);

    $component->call('sortBy', 'client_name');
    expect($component->subscriptions->pluck('id')->toArray())->toBe([$subB->id, $subA->id]);
});

test('csv export contains the requested columns and values', function () {
    $user = User::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider']);
    $client = Client::create(['name' => 'Acme Corp', 'email' => 'acme@test.test']);

    makeSubscription($client, $provider, [
        'domain_name' => 'acme.com',
        'renewal_type' => 'RecurringMonthly',
        'purchase_date' => '2026-01-01',
        'expiry_date' => '2026-02-01',
        'status' => 'Active',
    ]);

    $this->actingAs($user);
    $response = app(SubscriptionIndex::class)->export();

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('"Client Name","Client Email","Subscription Name","Renewal Type","Subscription Date","Renewal Date",Status');
    expect($content)->toContain('"Acme Corp",acme@test.test,acme.com,"Recurring (Monthly)",2026-01-01,2026-02-01,Active');
});
