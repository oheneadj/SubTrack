<?php

declare(strict_types=1);

use App\Actions\PrepareRenewalAction;
use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\OverviewDashboard;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('the Awaiting stat counts renewals actually awaiting payment (Pending), not the dead Invoiced status', function () {
    $client = Client::factory()->create();
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'service_type' => ServiceType::Domain,
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'awaiting-'.uniqid().'.test',
        'purchase_date' => now()->subYear(),
        'expiry_date' => now()->addDays(5),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => SubscriptionStatus::Active,
    ]);

    // PrepareRenewalAction is the only real path that creates a Renewal —
    // it's always created Pending, never Invoiced (that enum case is never
    // assigned anywhere in the app).
    app(PrepareRenewalAction::class)->execute($subscription, 1000, now()->addYear());

    $stats = Livewire::actingAs(User::factory()->create())
        ->test(OverviewDashboard::class)
        ->get('stats');

    expect($stats['awaiting'])->toBe(1);
});
