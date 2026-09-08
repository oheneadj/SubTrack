<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Project;

test('unrelatedFor creates the fallback project once and reuses it after', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'billing@acme.test']);

    $first = Project::unrelatedFor($client);
    $second = Project::unrelatedFor($client);

    expect($first->id)->toBe($second->id);
    expect($first->project_name)->toBe('Unrelated');
    expect(Project::where('client_id', $client->id)->where('project_name', 'Unrelated')->count())->toBe(1);
});

test('each client gets its own unrelated project', function () {
    $clientA = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $clientB = Client::create(['name' => 'Beta Co', 'email' => 'beta@test.test']);

    $projectA = Project::unrelatedFor($clientA);
    $projectB = Project::unrelatedFor($clientB);

    expect($projectA->id)->not->toBe($projectB->id);
});
