<?php

declare(strict_types=1);

use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Models\Client;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('the client trash toggle shows only deleted clients, and restore brings one back with its cascade', function () {
    $active = Client::factory()->create(['name' => 'Active Client']);
    $deleted = Client::factory()->create(['name' => 'Deleted Client']);
    $project = Project::create(['client_id' => $deleted->id, 'project_name' => 'Trashed Project']);
    $deleted->delete();

    $response = Livewire::actingAs(User::factory()->create())->test(ClientIndex::class);

    // Default view: only the active client, not the deleted one.
    $response->assertSee('Active Client')->assertDontSee('Deleted Client');

    $response->call('toggleTrash');

    // Trash view: only the deleted client, not the active one.
    $response->assertSee('Deleted Client')->assertDontSee('Active Client');

    $response->call('restore', $deleted->ulid);

    expect(Client::find($deleted->id))->not->toBeNull()
        ->and(Project::find($project->id))->not->toBeNull();
});

test('the project trash toggle shows only deleted projects, and restore brings one back with its subscriptions', function () {
    $client = Client::factory()->create();
    $active = Project::create(['client_id' => $client->id, 'project_name' => 'Active Project']);
    $deleted = Project::create(['client_id' => $client->id, 'project_name' => 'Deleted Project']);
    $subscription = Subscription::create([
        'project_id' => $deleted->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'trash-ui-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);
    $deleted->delete();

    $response = Livewire::actingAs(User::factory()->create())->test(ProjectIndex::class);

    $response->assertSee('Active Project')->assertDontSee('Deleted Project');

    $response->call('toggleTrash');

    $response->assertSee('Deleted Project')->assertDontSee('Active Project');

    $response->call('restore', $deleted->ulid);

    expect(Project::find($deleted->id))->not->toBeNull()
        ->and(Subscription::find($subscription->id))->not->toBeNull();
});

test('a project cascade-deleted along with its client still shows in the project trash', function () {
    $client = Client::factory()->create(['name' => 'Cascade Owner']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Cascade Trashed Project']);

    $client->delete();

    $response = Livewire::actingAs(User::factory()->create())->test(ProjectIndex::class);
    $response->call('toggleTrash');

    $response->assertSee('Cascade Trashed Project');
});
