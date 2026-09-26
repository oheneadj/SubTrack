<?php

declare(strict_types=1);

use App\Enums\EmailLogStatus;
use App\Mail\GenericClientMail;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\EmailLogEvent;
use App\Models\WebhookEvent;
use Illuminate\Support\Str;

function brevoWebhookPayload(array $overrides = []): array
{
    return array_merge([
        'event' => 'delivered',
        'email' => 'client@test.test',
        'message-id' => '<not-set@example.test>',
        'date' => now()->toDateTimeString(),
        'ts_event' => now()->timestamp,
    ], $overrides);
}

test('an unauthenticated webhook request is rejected', function () {
    config(['services.brevo.webhook_token' => 'correct-token']);

    $this->postJson('/webhooks/email/brevo?token=wrong-token', brevoWebhookPayload())
        ->assertUnauthorized();

    $this->postJson('/webhooks/email/brevo', brevoWebhookPayload())
        ->assertUnauthorized();
});

test('a delivered event marks the matching log as Delivered and records the raw event', function () {
    config(['services.brevo.webhook_token' => 'correct-token']);

    $client = Client::factory()->create();
    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi', 'body' => 'Body',
        'status' => EmailLogStatus::Sent,
        'sent_at' => now(),
    ]);
    $log->update(['message_id' => $log->generateMessageId()]);

    $this->postJson('/webhooks/email/brevo?token=correct-token', brevoWebhookPayload([
        'message-id' => "<{$log->message_id}>",
    ]))->assertOk();

    expect($log->fresh()->status)->toBe(EmailLogStatus::Delivered);
    expect($log->fresh()->delivered_at)->not->toBeNull();
    expect(EmailLogEvent::where('email_log_id', $log->id)->where('event', 'delivered')->count())->toBe(1);
});

test('a hard bounce event marks the log as Bounced with a reason', function () {
    config(['services.brevo.webhook_token' => 'correct-token']);

    $client = Client::factory()->create();
    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi', 'body' => 'Body',
        'status' => EmailLogStatus::Sent,
    ]);
    $log->update(['message_id' => $log->generateMessageId()]);

    $this->postJson('/webhooks/email/brevo?token=correct-token', brevoWebhookPayload([
        'event' => 'hard_bounce',
        'message-id' => "<{$log->message_id}>",
        'reason' => 'Mailbox does not exist',
    ]))->assertOk();

    expect($log->fresh()->status)->toBe(EmailLogStatus::Bounced);
    expect($log->fresh()->bounced_at)->not->toBeNull();
    expect($log->fresh()->error_message)->toBe('Mailbox does not exist');
});

test('opened and click events record engagement timestamps without changing delivery status', function () {
    config(['services.brevo.webhook_token' => 'correct-token']);

    $client = Client::factory()->create();
    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi', 'body' => 'Body',
        'status' => EmailLogStatus::Delivered,
        'delivered_at' => now(),
    ]);
    $log->update(['message_id' => $log->generateMessageId()]);

    $this->postJson('/webhooks/email/brevo?token=correct-token', brevoWebhookPayload([
        'event' => 'opened',
        'message-id' => "<{$log->message_id}>",
    ]))->assertOk();

    $this->postJson('/webhooks/email/brevo?token=correct-token', brevoWebhookPayload([
        'event' => 'click',
        'message-id' => "<{$log->message_id}>",
    ]))->assertOk();

    $log->refresh();
    expect($log->status)->toBe(EmailLogStatus::Delivered);
    expect($log->opened_at)->not->toBeNull();
    expect($log->clicked_at)->not->toBeNull();
});

test('a late bounce event never overwrites an already-delivered status', function () {
    config(['services.brevo.webhook_token' => 'correct-token']);

    $client = Client::factory()->create();
    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi', 'body' => 'Body',
        'status' => EmailLogStatus::Delivered,
        'delivered_at' => now(),
    ]);
    $log->update(['message_id' => $log->generateMessageId()]);

    $this->postJson('/webhooks/email/brevo?token=correct-token', brevoWebhookPayload([
        'event' => 'soft_bounce',
        'message-id' => "<{$log->message_id}>",
    ]))->assertOk();

    expect($log->fresh()->status)->toBe(EmailLogStatus::Delivered);
});

test('a redelivered webhook event is only processed once', function () {
    config(['services.brevo.webhook_token' => 'correct-token']);

    $client = Client::factory()->create();
    $log = EmailLog::create([
        'batch_id' => (string) Str::ulid(),
        'client_id' => $client->id,
        'mailable_class' => GenericClientMail::class,
        'to_email' => $client->email,
        'to_name' => $client->name,
        'subject' => 'Hi', 'body' => 'Body',
        'status' => EmailLogStatus::Sent,
    ]);
    $log->update(['message_id' => $log->generateMessageId()]);

    $payload = brevoWebhookPayload(['message-id' => "<{$log->message_id}>"]);

    $this->postJson('/webhooks/email/brevo?token=correct-token', $payload)->assertOk();
    $this->postJson('/webhooks/email/brevo?token=correct-token', $payload)->assertOk();

    expect(EmailLogEvent::where('email_log_id', $log->id)->count())->toBe(1);
    expect(WebhookEvent::where('gateway', 'brevo')->count())->toBe(1);
});

test('an event for an unknown message-id is safely ignored', function () {
    config(['services.brevo.webhook_token' => 'correct-token']);

    $this->postJson('/webhooks/email/brevo?token=correct-token', brevoWebhookPayload([
        'message-id' => '<unknown-ulid@example.test>',
    ]))->assertOk();

    expect(EmailLogEvent::count())->toBe(0);
});
