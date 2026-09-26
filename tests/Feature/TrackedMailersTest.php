<?php

declare(strict_types=1);

use App\Actions\GenerateReceiptAction;
use App\Enums\EmailLogStatus;
use App\Enums\InvoiceStatus;
use App\Livewire\Users\UserIndex;
use App\Mail\ClientMagicLinkMail;
use App\Mail\InvoiceMail;
use App\Mail\ReceiptMail;
use App\Mail\SubscriptionReminderMail;
use App\Mail\UserInviteMail;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('requesting a client magic link creates a tracked EmailLog and queues the mail', function () {
    Mail::fake();

    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);

    $this->post(route('client.magic-link.send'), ['email' => $client->email]);

    $log = EmailLog::where('client_id', $client->id)->where('mailable_class', ClientMagicLinkMail::class)->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(EmailLogStatus::Queued);
    expect($log->to_email)->toBe($client->email);

    Mail::assertQueued(ClientMagicLinkMail::class, fn ($mail) => $mail->emailLogId === $log->id);
});

test('inviting a user creates a tracked EmailLog with the plaintext password redacted', function () {
    Mail::fake();

    $admin = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserIndex::class)
        ->set('inviteName', 'New Person')
        ->set('inviteEmail', 'newperson@test.test')
        ->set('inviteRole', 'user')
        ->call('sendInvite');

    $log = EmailLog::where('mailable_class', UserInviteMail::class)->where('to_email', 'newperson@test.test')->firstOrFail();

    expect($log->status)->toBe(EmailLogStatus::Queued);
    expect($log->user_id)->toBe($admin->id);
    expect($log->body)->toContain('[REDACTED]');

    $newUser = User::where('email', 'newperson@test.test')->firstOrFail();
    expect($log->body)->not->toContain($newUser->password);
});

test('sending an invoice creates a tracked EmailLog with the invoice context', function () {
    Mail::fake();

    $client = Client::create(['name' => 'Acme Co', 'email' => 'acme@test.test']);
    $project = Project::create(['client_id' => $client->id, 'project_name' => 'Website']);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'INV-0001',
        'issued_date' => now(),
        'due_date' => now()->addDays(14),
        'total_amount' => 50000,
        'status' => InvoiceStatus::Draft,
    ]);

    app(NotificationService::class)->sendInvoice($invoice);

    $log = EmailLog::where('mailable_class', InvoiceMail::class)->where('to_email', $client->email)->firstOrFail();
    expect($log->status)->toBe(EmailLogStatus::Queued);
    expect($log->context)->toBe(['invoice_id' => $invoice->id]);

    Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->emailLogId === $log->id);
});

test('sending a subscription expiry reminder creates a tracked EmailLog with the subscription context', function () {
    Mail::fake();

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

    $log = EmailLog::where('mailable_class', SubscriptionReminderMail::class)->where('to_email', $client->email)->firstOrFail();
    expect($log->status)->toBe(EmailLogStatus::Queued);
    expect($log->context)->toBe(['subscription_id' => $subscription->id]);

    Mail::assertQueued(SubscriptionReminderMail::class, fn ($mail) => $mail->emailLogId === $log->id);
});

test('generating a receipt creates a tracked EmailLog with the receipt context', function () {
    Storage::fake('local');
    Mail::fake();

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

    $receipt = app(GenerateReceiptAction::class)->execute($subscription, 1500, 'Paid via bank transfer');

    $log = EmailLog::where('mailable_class', ReceiptMail::class)->where('to_email', $client->email)->firstOrFail();
    expect($log->status)->toBe(EmailLogStatus::Queued);
    expect($log->context)->toBe(['receipt_id' => $receipt->id, 'subscription_id' => $subscription->id]);

    Mail::assertQueued(ReceiptMail::class, fn ($mail) => $mail->emailLogId === $log->id);
});
