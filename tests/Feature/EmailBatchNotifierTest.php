<?php

declare(strict_types=1);

use App\Enums\EmailLogStatus;
use App\Mail\GenericClientMail;
use App\Mail\UserInviteMail;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailBatchNotifier;
use Illuminate\Support\Str;

function createEmailLogRow(array $overrides = []): EmailLog
{
    $client = Client::factory()->create();

    return EmailLog::create(array_merge([
        'batch_id' => (string) Str::ulid(),
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi',
        'body' => 'Body',
        'status' => EmailLogStatus::Queued,
    ], $overrides));
}

test('notifies the sender once all emails in a batch have left the queued state', function () {
    $admin = User::factory()->create();
    $batchId = (string) Str::ulid();

    createEmailLogRow(['batch_id' => $batchId, 'user_id' => $admin->id, 'status' => EmailLogStatus::Sent]);
    $log2 = createEmailLogRow(['batch_id' => $batchId, 'user_id' => $admin->id, 'status' => EmailLogStatus::Sent]);

    app(EmailBatchNotifier::class)->notifyIfComplete($log2);

    expect($admin->fresh()->unreadNotifications()->count())->toBe(1);
    $notification = $admin->fresh()->unreadNotifications()->first();
    expect($notification->data['message'])->toBe('Everyone on your list received it — 2 emails in total.');
    expect($notification->data['action_url'])->toBe(route('email-logs.show', $batchId));
});

test('does not notify while any email in the batch is still queued', function () {
    $admin = User::factory()->create();
    $batchId = (string) Str::ulid();

    createEmailLogRow(['batch_id' => $batchId, 'user_id' => $admin->id, 'status' => EmailLogStatus::Sent]);
    $stillQueued = createEmailLogRow(['batch_id' => $batchId, 'user_id' => $admin->id, 'status' => EmailLogStatus::Queued]);

    app(EmailBatchNotifier::class)->notifyIfComplete($stillQueued);

    expect($admin->fresh()->unreadNotifications()->count())->toBe(0);
});

test('reports a mixed sent/failed batch with both counts', function () {
    $admin = User::factory()->create();
    $batchId = (string) Str::ulid();

    createEmailLogRow(['batch_id' => $batchId, 'user_id' => $admin->id, 'status' => EmailLogStatus::Sent]);
    $lastOne = createEmailLogRow(['batch_id' => $batchId, 'user_id' => $admin->id, 'status' => EmailLogStatus::Failed]);

    app(EmailBatchNotifier::class)->notifyIfComplete($lastOne);

    $notification = $admin->fresh()->unreadNotifications()->first();
    expect($notification->data['message'])->toBe('1 email went out fine, but 1 email did not go through. Tap to see what happened.');
});

test('only notifies once per batch even if called multiple times', function () {
    $admin = User::factory()->create();
    $batchId = (string) Str::ulid();

    $log = createEmailLogRow(['batch_id' => $batchId, 'user_id' => $admin->id, 'status' => EmailLogStatus::Sent]);

    app(EmailBatchNotifier::class)->notifyIfComplete($log);
    app(EmailBatchNotifier::class)->notifyIfComplete($log);

    expect($admin->fresh()->unreadNotifications()->count())->toBe(1);
});

test('does not notify for non-Direct-Mailer mailables', function () {
    $admin = User::factory()->create();

    $log = createEmailLogRow([
        'user_id' => $admin->id,
        'status' => EmailLogStatus::Sent,
        'mailable_class' => UserInviteMail::class,
    ]);

    app(EmailBatchNotifier::class)->notifyIfComplete($log);

    expect($admin->fresh()->unreadNotifications()->count())->toBe(0);
});
