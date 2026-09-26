<?php

declare(strict_types=1);

use App\Livewire\Projects\ProjectShow;
use App\Livewire\Providers\ProviderShow;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

/**
 * Both project-show and provider-show list a project's/provider's
 * subscriptions in a table with no way to click through to the subscription
 * itself — the row had an Edit/Delete action-menu but no viewAction, and the
 * service name wasn't a link either.
 */
test('project show links each subscription row to its own show page', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'project_id' => $project->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    $html = Livewire::actingAs(User::factory()->create())
        ->test(ProjectShow::class, ['project' => $project])
        ->html();

    expect($html)->toContain(route('subscriptions.show', $subscription));
});

test('provider show links each subscription row to its own show page', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'project_id' => $project->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    $html = Livewire::actingAs(User::factory()->create())
        ->test(ProviderShow::class, ['provider' => $provider])
        ->html();

    expect($html)->toContain(route('subscriptions.show', $subscription));
});
