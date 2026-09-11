<?php

declare(strict_types=1);

use App\Livewire\Clients\ClientForm;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Clients\ClientShow;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

/**
 * Client editing used to navigate away to the clients index (?edit=...)
 * even when triggered from the client's own detail page. Extracted the
 * inline edit form into a standalone ClientForm component — mirroring how
 * ProjectForm already works — so both the index and the show page open the
 * exact same modal in place, no navigation involved.
 */
test('the edit button on both the client index and show pages opens the shared modal in place', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);

    $indexHtml = Livewire::actingAs($user)->test(ClientIndex::class)->html();
    $showHtml = Livewire::actingAs($user)->test(ClientShow::class, ['client' => $client])->html();

    foreach (['index' => $indexHtml, 'show' => $showHtml] as $html) {
        expect($html)->not->toContain('?edit=');
        expect($html)->toContain("Livewire.dispatchTo('clients.client-form', 'open-client-modal'");
        expect($html)->toContain('wire:name="clients.client-form"');
    }
});

test('the client form loads an existing client for editing and updates it in place', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);

    Livewire::actingAs($user)
        ->test(ClientForm::class)
        ->call('openClientModal', $client->ulid)
        ->assertSet('name', 'Acme Co')
        ->assertSet('isModal', true)
        ->set('name', 'Acme Corp International')
        ->call('save')
        ->assertDispatched('client-saved');

    expect($client->fresh()->name)->toBe('Acme Corp International');
});

test('the client form creates a new client when opened without an id', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientForm::class)
        ->call('openClientModal')
        ->assertSet('name', '')
        ->set('name', 'Brand New Co')
        ->set('email', 'brandnew@test.test')
        ->call('save')
        ->assertDispatched('client-saved');

    expect(Client::where('email', 'brandnew@test.test')->exists())->toBeTrue();
});
