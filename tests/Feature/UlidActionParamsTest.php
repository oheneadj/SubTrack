<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\ActivityLogs\ActivityLogIndex;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Providers\ProviderIndex;
use App\Livewire\Users\UserIndex;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

/**
 * Every action method below (edit, delete, confirmDelete, etc.) looks the
 * record up by `ulid`, e.g. `Client::where('ulid', $ulid)->firstOrFail()`.
 * The corresponding Blade views were passing the model's raw integer `id`
 * instead — firstOrFail() then throws ModelNotFoundException, which Laravel
 * renders as a 404. This covers every row action across the app that had
 * this exact bug, so it can't silently come back.
 */
test('client edit and delete resolve by ulid, not the raw id', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);

    Livewire::actingAs(User::factory()->create())
        ->test(ClientIndex::class)
        ->call('edit', $client->ulid)
        ->assertHasNoErrors();

    Livewire::actingAs(User::factory()->create())
        ->test(ClientIndex::class)
        ->call('openDeleteModal', $client->ulid)
        ->assertHasNoErrors();
});

test('project edit-dispatch and delete resolve by ulid, not the raw id', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    Livewire::actingAs(User::factory()->create())
        ->test(ProjectIndex::class)
        ->call('confirmDelete', $project->ulid)
        ->assertDispatched('open-modal', id: 'delete-project-modal');
});

test('a subscription can be deleted from the project show page', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'client_id' => $client->id, 'project_id' => $project->id, 'provider_id' => $provider->id,
        'service_type' => 'Domain', 'renewal_type' => 'RecurringAnnually', 'domain_name' => 'acme.com',
        'purchase_date' => now(), 'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000, 'renewal_cost_usd' => 1000, 'status' => 'Active',
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(ProjectShow::class, ['project' => $project])
        ->call('confirmDelete', $subscription->ulid)
        ->assertDispatched('open-modal', id: 'delete-subscription-modal')
        ->call('delete');

    expect(Subscription::find($subscription->id))->toBeNull();
});

test('provider edit and delete resolve by ulid, not the raw id', function () {
    $provider = Provider::create(['name' => 'Test Provider']);

    Livewire::actingAs(User::factory()->create())
        ->test(ProviderIndex::class)
        ->call('edit', $provider->ulid)
        ->assertHasNoErrors();

    Livewire::actingAs(User::factory()->create())
        ->test(ProviderIndex::class)
        ->call('openDeleteModal', $provider->ulid)
        ->assertHasNoErrors();
});

test('activity log details resolve by ulid, not the raw id', function () {
    $log = ActivityLog::create([
        'action' => 'model.created',
        'subject_type' => Client::class,
        'subject_id' => 1,
        'description' => 'Test log',
        'properties' => ['attributes' => ['name' => 'test']],
    ]);

    Livewire::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]))
        ->test(ActivityLogIndex::class)
        ->call('viewDetails', $log->ulid)
        ->assertSet('selectedLogUlid', $log->ulid);
});

test('user delete resolves by ulid, not the raw id', function () {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $target = User::factory()->create();

    Livewire::actingAs($user)
        ->test(UserIndex::class)
        ->call('confirmDelete', $target->ulid)
        ->assertHasNoErrors();
});
