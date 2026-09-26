<?php

declare(strict_types=1);

use App\Actions\DispatchClientMailAction;
use App\Enums\EmailLogStatus;
use App\Enums\UserRole;
use App\Livewire\EmailLogs\EmailBatchShow;
use App\Livewire\EmailLogs\EmailLogIndex;
use App\Livewire\MailTemplates\DirectMailer;
use App\Mail\GenericClientMail;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('guests are redirected to the login page for both email log pages', function () {
    $this->get(route('email-logs.index'))->assertRedirect(route('login'));
    $this->get(route('email-logs.show', 'whatever'))->assertRedirect(route('login'));
});

test('non super admins cannot access the email log', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)->get(route('email-logs.index'))->assertForbidden();
});

test('sending from the direct mailer creates an EmailLog row per recipient under one batch', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();

    Livewire::actingAs($admin)
        ->test(DirectMailer::class)
        ->set('selectedClients', [$clientA->ulid, $clientB->ulid])
        ->set('subject', 'Hello {client_name}')
        ->set('body', 'Message body')
        ->call('send');

    expect(EmailLog::count())->toBe(2);

    $logs = EmailLog::all();
    expect($logs->pluck('batch_id')->unique())->toHaveCount(1);
    expect($logs->pluck('status')->unique()->first())->toBe(EmailLogStatus::Queued);
    expect($logs->firstWhere('client_id', $clientA->id)->subject)->toBe("Hello {$clientA->name}");
});

test('a message actually being sent marks its log row as Sent', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $client = Client::factory()->create();

    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'user_id' => $admin->id,
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi',
        'body' => 'Body',
        'status' => EmailLogStatus::Queued,
    ]);

    Mail::to($client->email)->send(new GenericClientMail($client, 'Hi', 'Body', null, $log->id));

    expect($log->fresh()->status)->toBe(EmailLogStatus::Sent);
    expect($log->fresh()->sent_at)->not->toBeNull();
});

test('a failed mailable marks its log row as Failed with the error message', function () {
    $client = Client::factory()->create();

    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi',
        'body' => 'Body',
        'status' => EmailLogStatus::Queued,
    ]);

    $mailable = new GenericClientMail($client, 'Hi', 'Body', null, $log->id);
    $mailable->failed(new Exception('SMTP connection refused'));

    expect($log->fresh()->status)->toBe(EmailLogStatus::Failed);
    expect($log->fresh()->error_message)->toBe('SMTP connection refused');
});

test('resending a failed individual email requeues it and clears the error', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $client = Client::factory()->create();

    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'user_id' => $admin->id,
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi',
        'body' => 'Body',
        'status' => EmailLogStatus::Failed,
        'error_message' => 'Something went wrong',
    ]);

    Livewire::actingAs($admin)
        ->test(EmailBatchShow::class, ['batchId' => $log->batch_id])
        ->call('resend', $log->ulid);

    expect($log->fresh()->status)->toBe(EmailLogStatus::Queued);
    expect($log->fresh()->error_message)->toBeNull();
    Mail::assertQueued(GenericClientMail::class, fn ($mail) => $mail->emailLogId === $log->id);
});

test('resend all failed requeues every failed email in a batch, leaving sent ones alone', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $client = Client::factory()->create();
    $batchId = (string) Str::ulid();

    $failed = EmailLog::create([
        'batch_id' => $batchId, 'client_id' => $client->id, 'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email, 'to_name' => $client->name, 'subject' => 'Hi', 'body' => 'Body',
        'status' => EmailLogStatus::Failed, 'error_message' => 'Boom',
    ]);
    $sent = EmailLog::create([
        'batch_id' => $batchId, 'client_id' => $client->id, 'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email, 'to_name' => $client->name, 'subject' => 'Hi 2', 'body' => 'Body 2',
        'status' => EmailLogStatus::Sent, 'sent_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(EmailLogIndex::class)
        ->call('resendAllFailed', $batchId);

    expect($failed->fresh()->status)->toBe(EmailLogStatus::Queued);
    expect($sent->fresh()->status)->toBe(EmailLogStatus::Sent);
    Mail::assertQueuedCount(1);
});

test('dispatch action renders the subject per client and tags the mailable with the log id', function () {
    Mail::fake();

    $client = Client::factory()->create(['name' => 'Acme Co']);
    $batchId = (string) Str::ulid();

    $log = app(DispatchClientMailAction::class)->dispatch(
        $client,
        'Hello {client_name}',
        'Dear {client_name}.',
        null,
        $batchId,
        null,
    );

    expect($log->subject)->toBe('Hello Acme Co');
    expect($log->batch_id)->toBe($batchId);
    Mail::assertQueued(GenericClientMail::class, fn ($mail) => $mail->emailLogId === $log->id);
});
