<?php

declare(strict_types=1);

use App\Actions\GenerateReceiptAction;
use App\Mail\UserInviteMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Services\EmailLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Mail::fake() and the test suite's default `sync` queue connection both
 * skip real job serialization, which is exactly what let a real bug through
 * previously: EmailLogger::track() was calling $mailable->render() on the
 * same instance that got queued afterward, and render() mutates that
 * instance with an unserializable Closure (via Mailable::headers() ->
 * withSymfonyMessage()). These tests force the `database` queue connection
 * (which does serialize) and don't fake Mail, so a regression here throws
 * for real instead of passing silently.
 */
beforeEach(function () {
    config(['queue.default' => 'database']);
});

test('a receipt mail queued through EmailLogger survives real job serialization', function () {
    Storage::fake('local');

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

    app(GenerateReceiptAction::class)->execute($subscription, 1500, 'Paid via bank transfer');

    expect(DB::table('jobs')->count())->toBe(1);
});

test('an invoice mail queued through EmailLogger survives real job serialization', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Website']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-0001',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 50000,
        'status' => 'Draft',
    ]);

    app(NotificationService::class)->sendInvoice($invoice);

    expect(DB::table('jobs')->count())->toBe(1);
});

test('a subscription reminder mail queued through EmailLogger survives real job serialization', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $provider = Provider::create(['name' => 'Test Provider']);
    $subscription = Subscription::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'service_type' => 'Domain',
        'renewal_type' => 'RecurringAnnually',
        'domain_name' => 'acme.com',
        'purchase_date' => now(),
        'expiry_date' => now()->addDays(7),
        'purchase_cost_usd' => 1000,
        'renewal_cost_usd' => 1000,
        'status' => 'Active',
    ]);

    app(NotificationService::class)->sendExpiryReminder($subscription);

    expect(DB::table('jobs')->count())->toBe(1);
});

test('a user invite mail queued through EmailLogger survives real job serialization', function () {
    $mail = new UserInviteMail('New Person', 'newperson@test.test', 'p4ssw0rd!', route('login'));
    app(EmailLogger::class)->track($mail, 'newperson@test.test', 'New Person');

    Mail::to('newperson@test.test')->queue($mail);

    expect(DB::table('jobs')->count())->toBe(1);
});

test('a client magic link mail queued through EmailLogger survives real job serialization', function () {
    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);

    $this->post(route('client.magic-link.send'), ['email' => $client->email]);

    expect(DB::table('jobs')->count())->toBe(1);
});
