<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailLogStatus;
use App\Mail\GenericClientMail;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\Subscription;
use App\Services\ClientMailPersonalizer;
use Illuminate\Support\Facades\Mail;

/**
 * Queues a personalized client email and records it as an EmailLog row,
 * so every Direct Mailer send (individual or bulk) is auditable and
 * failed attempts can be resent later.
 */
class DispatchClientMailAction
{
    public function __construct(
        private readonly ClientMailPersonalizer $personalizer,
    ) {}

    /**
     * Queue a brand-new send for a client, as part of the given batch.
     */
    public function dispatch(
        Client $client,
        string $subject,
        string $body,
        ?Subscription $subscription,
        string $batchId,
        ?int $userId,
    ): EmailLog {
        $rendered = $this->personalizer->render($client, $subject, $body, $subscription);

        $log = EmailLog::create([
            'batch_id' => $batchId,
            'user_id' => $userId,
            'client_id' => $client->id,
            'mailable_class' => GenericClientMail::class,
            'to_email' => $client->email,
            'to_name' => $client->name,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'context' => ['subscription_id' => $subscription?->id],
            'status' => EmailLogStatus::Queued,
        ]);

        $log->update(['message_id' => $log->generateMessageId()]);

        Mail::to($client->email)->queue(new GenericClientMail(
            $client,
            $rendered['subject'],
            $rendered['body'],
            $subscription,
            $log->id,
        ));

        return $log;
    }

    /**
     * Re-attempt a previously failed send, reusing the same log row and its
     * originally-snapshotted subject/body — a resend must deliver exactly
     * what was supposed to go out the first time, not a freshly re-rendered
     * version that could differ if the client or business settings changed
     * since.
     */
    public function resend(EmailLog $log): void
    {
        $client = $log->client;

        if (! $client) {
            $log->update([
                'status' => EmailLogStatus::Failed,
                'error_message' => 'Cannot resend: the original client no longer exists.',
            ]);

            return;
        }

        $subscriptionId = $log->context['subscription_id'] ?? null;
        $subscription = $subscriptionId ? Subscription::find($subscriptionId) : null;

        $log->update([
            'status' => EmailLogStatus::Queued,
            'error_message' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'bounced_at' => null,
        ]);

        Mail::to($log->to_email)->queue(new GenericClientMail(
            $client,
            $log->subject,
            $log->body,
            $subscription,
            $log->id,
        ));
    }
}
