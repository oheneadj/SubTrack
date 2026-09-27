<?php

declare(strict_types=1);

use App\Livewire\Dashboard\OverviewDashboard;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('critical and warning expiration tables link each subscription to its own show page', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Acme Project']);
    $provider = Provider::create(['name' => 'Test Provider']);

    $critical = Subscription::create([
        'project_id' => $project->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'critical.com',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(3),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Expiring',
    ]);

    $warning = Subscription::create([
        'project_id' => $project->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'warning.com',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(20),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Expiring',
    ]);

    $html = Livewire::actingAs(User::factory()->create())
        ->test(OverviewDashboard::class)
        ->html();

    expect($html)->toContain(route('subscriptions.show', $critical));
    expect($html)->toContain(route('subscriptions.show', $warning));
});
