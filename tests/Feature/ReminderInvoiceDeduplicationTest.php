<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionRenewalType;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Provider;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

test('multiple reminders for the same never-renewed subscription reuse the same draft invoice', function () {
    Mail::fake();

    $client = Client::factory()->create();
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => SubscriptionRenewalType::RecurringAnnually,
        'domain_name' => 'example.com',
        'purchase_date' => now()->subMonths(11),
        'expiry_date' => now()->addDays(30),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 5000,
        'status' => 'Active',
    ]);

    // This subscription has never been renewed before, so no Renewal row
    // exists yet — the exact scenario that used to create a fresh draft
    // invoice on every single reminder call.
    $notificationService = app(NotificationService::class);
    $notificationService->sendExpiryReminder($subscription);
    $notificationService->sendExpiryReminder($subscription);
    $notificationService->sendExpiryReminder($subscription);

    expect(Invoice::where('client_id', $client->id)->where('status', InvoiceStatus::Draft)->count())->toBe(1);
});
