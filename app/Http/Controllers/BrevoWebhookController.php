<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EmailLogStatus;
use App\Models\EmailLog;
use App\Models\EmailLogEvent;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Receives delivery/bounce/engagement events from Brevo's transactional
 * webhook (Transactional > Settings > Webhooks in the Brevo dashboard) and
 * updates the matching EmailLog row.
 *
 * Brevo doesn't sign webhook payloads, so authenticity is verified via a
 * shared secret appended to the configured webhook URL as ?token=...
 */
class BrevoWebhookController extends Controller
{
    private const STATUS_EVENTS = [
        'delivered' => ['status' => EmailLogStatus::Delivered, 'column' => 'delivered_at'],
        'hard_bounce' => ['status' => EmailLogStatus::Bounced, 'column' => 'bounced_at'],
        'soft_bounce' => ['status' => EmailLogStatus::Bounced, 'column' => 'bounced_at'],
        'blocked' => ['status' => EmailLogStatus::Blocked, 'column' => null],
        'invalid_email' => ['status' => EmailLogStatus::Blocked, 'column' => null],
        'spam' => ['status' => EmailLogStatus::Complained, 'column' => null],
    ];

    public function __invoke(Request $request): Response
    {
        if (! $this->tokenIsValid($request)) {
            Log::warning('Brevo webhook received with an invalid or missing token.');

            return response('Unauthorized', 401);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $messageId = $payload['message-id'] ?? null;

        if (! $event || ! $messageId) {
            return response('', 200);
        }

        $eventId = $this->eventFingerprint($event, $messageId, $payload);
        if (! $this->recordEventOnce($eventId)) {
            // Already processed this exact event — Brevo redelivered it. No-op.
            return response('', 200);
        }

        $log = $this->findLogByMessageId($messageId);
        if (! $log) {
            Log::warning('Brevo webhook: no matching EmailLog for message-id.', ['message_id' => $messageId]);

            return response('', 200);
        }

        $occurredAt = isset($payload['ts_event'])
            ? Carbon::createFromTimestamp($payload['ts_event'])
            : now();

        EmailLogEvent::create([
            'email_log_id' => $log->id,
            'event' => $event,
            'payload' => $payload,
            'occurred_at' => $occurredAt,
        ]);

        $this->applyStatusTransition($log, $event, $occurredAt, $payload);

        return response('', 200);
    }

    private function tokenIsValid(Request $request): bool
    {
        $expected = config('services.brevo.webhook_token');

        return $expected && hash_equals($expected, (string) $request->query('token'));
    }

    /**
     * A stable fingerprint for this exact event delivery, so a redelivered
     * webhook (Brevo retries on non-200 responses) is never double-applied.
     */
    private function eventFingerprint(string $event, string $messageId, array $payload): string
    {
        return md5($event.'|'.$messageId.'|'.($payload['ts_event'] ?? $payload['date'] ?? ''));
    }

    private function recordEventOnce(string $eventId): bool
    {
        try {
            WebhookEvent::create(['gateway' => 'brevo', 'event_id' => $eventId]);

            return true;
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * Brevo echoes back the Message-ID we set at send time, wrapped in
     * angle brackets (e.g. "<01ABC...@ourapp.test>"). The local part before
     * the @ is the EmailLog's own ULID.
     */
    private function findLogByMessageId(string $messageId): ?EmailLog
    {
        $ulid = explode('@', trim($messageId, '<>'))[0] ?? null;

        return $ulid ? EmailLog::where('ulid', $ulid)->first() : null;
    }

    private function applyStatusTransition(EmailLog $log, string $event, Carbon $occurredAt, array $payload): void
    {
        if (in_array($event, ['opened', 'unique_opened'], true)) {
            $log->update(['opened_at' => $log->opened_at ?? $occurredAt]);

            return;
        }

        if ($event === 'click') {
            $log->update(['clicked_at' => $log->clicked_at ?? $occurredAt]);

            return;
        }

        $config = self::STATUS_EVENTS[$event] ?? null;
        if (! $config) {
            return;
        }

        // A message that's already bounced/blocked/complained shouldn't be
        // silently overwritten back to Delivered by a late/out-of-order event.
        if ($log->status === EmailLogStatus::Delivered && $config['status'] !== EmailLogStatus::Delivered) {
            return;
        }

        $update = ['status' => $config['status']];

        if ($config['column']) {
            $update[$config['column']] = $occurredAt;
        }

        if ($config['status'] !== EmailLogStatus::Delivered) {
            $update['error_message'] = $payload['reason'] ?? ucfirst(str_replace('_', ' ', $event));
        }

        $log->update($update);
    }
}
