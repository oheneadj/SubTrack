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
 * `$dispatchTo(...)` is a client-side Livewire JS call (used to tell a
 * modal's child component what to load) and must live in an Alpine
 * directive like @click — never in wire:click, which tries (and fails)
 * to call it as a server-side PHP action. Covers action-menu.blade.php's
 * editAction handling plus the direct button usages on project/client
 * pages that open the shared project-form modal.
 */
test('project and client edit buttons never put a JS dispatch call in wire:click', function () {
    $user = User::factory()->create();
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);

    $indexHtml = Livewire::actingAs($user)->test(ProjectIndex::class)->html();
    $showHtml = Livewire::actingAs($user)->test(ProjectShow::class, ['project' => $project])->html();
    $clientShowHtml = Livewire::actingAs($user)->test(ClientShow::class, ['client' => $client])->html();

    foreach ([$indexHtml, $showHtml, $clientShowHtml] as $html) {
        expect($html)->not->toContain('wire:click="$dispatch');
        expect($html)->toContain('dispatchTo(');
    }
});
