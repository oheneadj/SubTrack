<?php

declare(strict_types=1);

use App\Livewire\Invoices\InvoiceBuilder;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Subscriptions\SubscriptionForm;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * project-show.blade.php's "Add Subscription" and "New Invoice" links pass
 * project/client identifiers as query-string params (?projectId=...,
 * ?clientId=...) that SubscriptionForm/InvoiceBuilder look up by ulid —
 * they were passing the raw internal id instead, so the target form never
 * pre-filled its project/client and silently landed empty. Same bug class
 * as the row-action ulid mismatches fixed earlier, just hiding inside a
 * route() query array instead of a wire:click argument.
 */
test('project show renders its subscription/invoice links with the ulid, not the raw id', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    $html = Livewire::actingAs(User::factory()->create())
        ->test(ProjectShow::class, ['project' => $project])
        ->html();

    expect($html)->toContain("projectId={$project->ulid}");
    expect($html)->toContain("clientId={$client->ulid}");
    expect($html)->not->toContain("projectId={$project->id}&");
});

test('the subscription form pre-fills project and client from a ulid query param', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    Livewire::actingAs(User::factory()->create())
        ->withQueryParams(['projectId' => $project->ulid])
        ->test(SubscriptionForm::class)
        ->assertSet('project_id', $project->id)
        ->assertSet('client_id', $client->id);
});

test('the invoice builder pre-fills client and project from ulid query params', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    Livewire::actingAs(User::factory()->create())
        ->withQueryParams(['clientId' => $client->ulid, 'projectId' => $project->ulid])
        ->test(InvoiceBuilder::class)
        ->assertSet('client_id', $client->id)
        ->assertSet('project_id', $project->id);
});
