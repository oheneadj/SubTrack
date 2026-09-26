<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailLogStatus;
use App\Models\EmailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;

/**
 * Creates the EmailLog row for any trackable Mailable (one that uses
 * App\Mail\Concerns\TracksEmailDelivery) and tags the mailable with it,
 * ready to be sent/queued.
 *
 * Renders the mailable to capture its actual subject/body as the log's
 * snapshot — this is for display/audit only; unlike Direct Mailer's
 * GenericClientMail, these mailables aren't reconstructed from the stored
 * snapshot on resend, so no special un-rendering concerns apply here.
 */
class EmailLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function track(
        Mailable $mailable,
        string $toEmail,
        ?string $toName = null,
        ?int $clientId = null,
        ?int $userId = null,
        ?string $batchId = null,
        array $context = [],
        ?callable $redact = null,
    ): EmailLog {
        // Rendered on a clone, never the instance itself: Mailable::render()
        // triggers prepareMailableForDelivery(), which (via headers()) calls
        // withSymfonyMessage(Closure) — mutating $this permanently. Queuing
        // that same, now-poisoned instance afterward fails job serialization
        // ("Serialization of 'Closure' is not allowed"). Mail::fake() in
        // tests never exercises real serialization, so this doesn't surface
        // there — only against a real queue connection.
        $preview = clone $mailable;
        $subject = $preview->envelope()->subject ?? '(no subject)';
        $body = $preview->render();

        if ($redact) {
            $body = $redact($body);
        }

        $log = EmailLog::create([
            'batch_id' => $batchId ?? (string) Str::ulid(),
            'user_id' => $userId,
            'client_id' => $clientId,
            'mailable_class' => $mailable::class,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'body' => $body,
            'context' => $context,
            'status' => EmailLogStatus::Queued,
        ]);

        $log->update(['message_id' => $log->generateMessageId()]);

        $mailable->emailLogId = $log->id;

        return $log;
    }
}
