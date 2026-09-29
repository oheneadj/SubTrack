<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('deleting a client cascades to its projects and directly-attached subscriptions', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Cascade Project']);
    $directSubscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'direct-cascade-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    $client->delete();

    expect(Client::find($client->id))->toBeNull()
        ->and(Client::withTrashed()->find($client->id)->trashed())->toBeTrue()
        ->and(Project::withTrashed()->find($project->id)->trashed())->toBeTrue()
        ->and(Subscription::withTrashed()->find($directSubscription->id)->trashed())->toBeTrue();
});

test('deleting a client cascades through its projects to their subscriptions too', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Nested Cascade Project']);
    $projectSubscription = Subscription::create([
        'project_id' => $project->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'project-cascade-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    $client->delete();

    expect(Subscription::withTrashed()->find($projectSubscription->id)->trashed())->toBeTrue();
});

test('deleting a client does not delete its invoices — money history is preserved', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-CASCADE-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 10000,
        'status' => InvoiceStatus::Sent,
    ]);

    $client->delete();

    expect(Invoice::find($invoice->id))->not->toBeNull();
});

test('deleting a project cascades to its subscriptions but preserves its invoices', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Solo Project']);
    $subscription = Subscription::create([
        'project_id' => $project->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'project-only-cascade-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-PROJ-CASCADE-'.uniqid(),
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 5000,
        'status' => InvoiceStatus::Sent,
    ]);

    $project->delete();

    expect(Subscription::withTrashed()->find($subscription->id)->trashed())->toBeTrue()
        ->and(Invoice::find($invoice->id))->not->toBeNull();
});

test('the client index delete-with-password flow triggers the full cascade', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'UI Cascade Project']);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientIndex::class)
        ->call('openDeleteModal', $client->ulid)
        ->set('deletePassword', 'password')
        ->call('deleteWithPassword')
        ->assertHasNoErrors();

    expect(Client::withTrashed()->find($client->id)->trashed())->toBeTrue()
        ->and(Project::withTrashed()->find($project->id)->trashed())->toBeTrue();
});

test('the project index delete flow cascades to subscriptions', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'UI Project Delete']);
    $subscription = Subscription::create([
        'project_id' => $project->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'ui-project-cascade-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(ProjectIndex::class)
        ->call('confirmDelete', $project->ulid)
        ->call('delete');

    expect(Project::withTrashed()->find($project->id)->trashed())->toBeTrue()
        ->and(Subscription::withTrashed()->find($subscription->id)->trashed())->toBeTrue();
});

test('restoring a client cascades to its projects and directly-attached subscriptions', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Restore Cascade Project']);
    $directSubscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'restore-direct-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    $client->delete();
    $client->restore();

    expect(Client::find($client->id))->not->toBeNull()
        ->and(Project::find($project->id))->not->toBeNull()
        ->and(Subscription::find($directSubscription->id))->not->toBeNull();
});

test('restoring a client cascades through its projects to their subscriptions too', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Restore Nested Project']);
    $projectSubscription = Subscription::create([
        'project_id' => $project->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'restore-nested-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    $client->delete();
    $client->restore();

    expect(Subscription::find($projectSubscription->id))->not->toBeNull();
});

test('restoring a project cascades to its subscriptions', function () {
    $client = Client::factory()->create();
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Restore Solo Project']);
    $subscription = Subscription::create([
        'project_id' => $project->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'restore-solo-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    $project->delete();
    $project->restore();

    expect(Subscription::find($subscription->id))->not->toBeNull();
});
