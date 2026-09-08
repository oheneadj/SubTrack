<?php

declare(strict_types=1);

use App\Enums\ActivityEventType;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Receipt;
use App\Models\Renewal;
use App\Models\Subscription;

test('creating a subscription logs a SubscriptionCreated dashboard activity', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    expect(DashboardActivityLog::where('event_type', ActivityEventType::SubscriptionCreated)
        ->where('client_id', $client->id)
        ->count())->toBe(1);
});

test('a subscription created via a project resolves the client through the project, not the relation', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Rocket Launch']);
    $provider = Provider::create(['name' => 'Test Provider']);

    Subscription::create([
        'project_id' => $project->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'rocket.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    expect(DashboardActivityLog::where('event_type', ActivityEventType::SubscriptionCreated)
        ->where('client_id', $client->id)
        ->exists())->toBeTrue();
});

test('a subscription status change to Expiring or Expired logs the matching dashboard activity', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addDays(5),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    $subscription->update(['status' => 'Expiring']);
    expect(DashboardActivityLog::where('event_type', ActivityEventType::SubscriptionExpiring)->exists())->toBeTrue();

    $subscription->update(['status' => 'Expired']);
    expect(DashboardActivityLog::where('event_type', ActivityEventType::SubscriptionExpired)->exists())->toBeTrue();
});

test('confirming a renewal logs a RenewalConfirmed dashboard activity', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    Renewal::create([
        'subscription_id' => $subscription->id,
        'due_date' => now(),
        'provider_cost_usd' => 1000,
        'client_cost_usd' => 1200,
        'payment_status' => PaymentStatus::Renewed,
    ]);

    expect(DashboardActivityLog::where('event_type', ActivityEventType::RenewalConfirmed)
        ->where('client_id', $client->id)
        ->count())->toBe(1);
});

test('generating a receipt logs a ReceiptGenerated dashboard activity', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);

    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addYear(),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    $receipt = Receipt::create([
        'subscription_id' => $subscription->id,
        'client_id' => $client->id,
        'receipt_number' => 'RCT-2026-001',
        'amount_usd' => 1500,
        'issued_date' => now(),
    ]);

    expect(DashboardActivityLog::where('event_type', ActivityEventType::ReceiptGenerated)
        ->where('client_id', $client->id)
        ->count())->toBe(1);

    // Receipt also uses LogsActivity, so it must show up in the generic audit trail too.
    expect(ActivityLog::where('subject_type', Receipt::class)
        ->where('subject_id', $receipt->id)
        ->where('action', 'model.created')
        ->exists())->toBeTrue();
});
