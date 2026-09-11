<?php

declare(strict_types=1);

use App\Livewire\Clients\ClientShow;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

/**
 * The client detail page previously buried subscriptions in a capped,
 * summary-only sidebar card and had no way to add a project or subscription
 * directly from it. Subscriptions now get their own full section in the
 * main column, directly below Projects, with an "Add Subscription" button
 * — matching how Projects already has "New Project".
 */
test('the client show page has add-project and add-subscription actions, with subscriptions below projects', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    Subscription::create([
        'client_id' => $client->id, 'project_id' => $project->id, 'provider_id' => $provider->id,
        'service_type' => 'Domain', 'renewal_type' => 'RecurringAnnually', 'domain_name' => 'acme.com',
        'purchase_date' => now(), 'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000, 'renewal_cost_usd' => 1000, 'status' => 'Active',
    ]);

    $html = Livewire::actingAs($user)->test(ClientShow::class, ['client' => $client])->html();

    expect($html)->toContain('New Project');
    expect($html)->toContain('Add Subscription');
    expect($html)->toContain("subscriptions/create?clientId={$client->ulid}");
    expect($html)->toContain('acme.com');

    $projectsPos = strpos($html, '>Projects</h3>');
    $subscriptionsPos = strpos($html, '>Subscriptions</h3>');
    $invoicesPos = strpos($html, '>Recent Invoices</h3>');

    expect($projectsPos)->not->toBeFalse();
    expect($subscriptionsPos)->not->toBeFalse();
    expect($invoicesPos)->not->toBeFalse();
    expect($projectsPos)->toBeLessThan($subscriptionsPos);
    expect($subscriptionsPos)->toBeLessThan($invoicesPos);
});
