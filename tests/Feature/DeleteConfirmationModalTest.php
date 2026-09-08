<?php

declare(strict_types=1);

use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Subscriptions\SubscriptionIndex;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

/**
 * confirm-modal.blade.php listens for the "open-modal" browser event and
 * checks `$event.detail.id` — that only works if the event is dispatched
 * with a named `id:` argument. A bare string or an array positional arg
 * produces a different event.detail shape and the modal silently never
 * opens. Regression coverage for both index pages using the shared modal.
 */
test('confirming a subscription delete dispatches open-modal with a named id', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'client_id' => $client->id, 'provider_id' => $provider->id, 'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually', 'domain_name' => 'acme.com',
        'purchase_date' => now(), 'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000, 'renewal_cost_usd' => 1000, 'status' => 'Active',
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionIndex::class)
        ->call('confirmDelete', $subscription->ulid)
        ->assertDispatched('open-modal', id: 'confirm-delete-subscription');
});

test('confirming a project delete dispatches open-modal with a named id', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    Livewire::actingAs($user)
        ->test(ProjectIndex::class)
        ->call('confirmDelete', $project->ulid)
        ->assertDispatched('open-modal', id: 'delete-project-modal');
});
