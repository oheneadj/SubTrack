<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;

/**
 * Regression coverage for the "view/edit routes 404" report. These all
 * passed once the underlying bug (User::factory() leaving is_active,
 * requires_password_change, and role unset, which EnsureUserIsActive then
 * misreads as an inactive account and force-logs out) was fixed — that
 * bug was silently masking every HTTP-level test in this suite, not just
 * these ones, so this is deliberately a real end-to-end HTTP check rather
 * than a Livewire::test() component mount.
 */
test('client, project, provider, subscription, and invoice show/edit routes resolve', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'client_id' => $client->id, 'provider_id' => $provider->id, 'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually', 'domain_name' => 'acme.com',
        'purchase_date' => now(), 'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000, 'renewal_cost_usd' => 1000, 'status' => 'Active',
    ]);
    $invoice = Invoice::create([
        'client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => 'RC-0001',
        'issued_date' => now(), 'due_date' => now()->addDays(14),
    ]);

    $this->actingAs($admin);

    $this->get(route('clients.show', $client))->assertOk();
    $this->get(route('projects.show', $project))->assertOk();
    $this->get(route('providers.show', $provider))->assertOk();
    $this->get(route('subscriptions.show', $subscription))->assertOk();
    $this->get(route('invoices.edit', $invoice))->assertOk();
});

test('a fresh factory user is not force-logged-out by EnsureUserIsActive', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
