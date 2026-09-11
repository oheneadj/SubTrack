<?php

declare(strict_types=1);

use App\Livewire\Clients\ClientShow;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * Dispatching to a specific Livewire component by name (to tell a modal's
 * child component what to load) is a client-side call and must live in an
 * Alpine click directive — never in wire:click, which tries (and fails) to
 * call it as a server-side PHP action. It also must be spelled
 * `Livewire.dispatchTo(...)` (the actual global JS API), never a bare
 * `$dispatchTo(...)`: Livewire only registers `$dispatch` as an Alpine
 * magic, not `$dispatchTo`, so the bare form throws a ReferenceError and
 * silently no-ops (the modal still opens, since that's a separate half of
 * the same click expression, but the form never receives the record to
 * load and renders empty instead of pre-filled). Covers action-menu.blade.php's
 * editAction handling plus the direct button usages on project/client
 * pages that open the shared project-form modal.
 */
test('project and client edit buttons use Livewire.dispatchTo in @click, never in wire:click or bare', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    $indexHtml = Livewire::actingAs($user)->test(ProjectIndex::class)->html();
    $showHtml = Livewire::actingAs($user)->test(ProjectShow::class, ['project' => $project])->html();
    $clientShowHtml = Livewire::actingAs($user)->test(ClientShow::class, ['client' => $client])->html();

    foreach ([$indexHtml, $showHtml, $clientShowHtml] as $html) {
        expect($html)->not->toContain('wire:click="$dispatch');
        expect($html)->not->toContain('$dispatchTo(');
        expect($html)->toContain('Livewire.dispatchTo(');
    }
});
