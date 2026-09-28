<?php

declare(strict_types=1);

use App\Livewire\Invoices\InvoiceBuilder;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('changing a field dispatches a browser event carrying the current draft', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceBuilder::class)
        ->set('notes', 'Please pay promptly')
        ->assertDispatched('invoice-draft-changed');
});

test('editing an existing invoice never dispatches draft events', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-DRAFT-0001',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 5000,
        'status' => 'Draft',
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceBuilder::class, ['invoice' => $invoice])
        ->set('notes', 'Updated notes')
        ->assertNotDispatched('invoice-draft-changed');
});

test('restoring a draft repopulates the form and flags it as restored', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    $draft = [
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-RESTORED-0001',
        'issued_date' => '2026-01-01',
        'due_date' => '2026-01-15',
        'status' => 'Draft',
        'notes' => 'Restored notes',
        'tax_rate' => 5,
        'items' => [
            ['description' => 'Consulting', 'quantity' => 2, 'unit_price' => 100, 'total' => 200, 'subscription_id' => null],
        ],
    ];

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceBuilder::class)
        ->call('restoreDraft', $draft)
        ->assertSet('client_id', $client->id)
        ->assertSet('project_id', $project->id)
        ->assertSet('notes', 'Restored notes')
        ->assertSet('draftRestored', true)
        ->assertSet('items.0.description', 'Consulting')
        ->assertSet('subtotal', 200.0);
});

test('discarding a draft resets the form to a fresh blank state', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceBuilder::class)
        ->set('client_id', $client->id)
        ->set('notes', 'Some notes')
        ->call('discardDraft')
        ->assertSet('client_id', null)
        ->assertSet('notes', '')
        ->assertSet('draftRestored', false)
        ->assertDispatched('invoice-draft-cleared');
});

test('saving a new invoice clears the browser draft', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    Livewire::actingAs(User::factory()->create())
        ->test(InvoiceBuilder::class)
        ->set('client_id', $client->id)
        ->set('project_id', $project->id)
        ->set('items.0.description', 'Website build')
        ->set('items.0.unit_price', 500)
        ->call('save')
        ->assertDispatched('invoice-draft-cleared');
});
