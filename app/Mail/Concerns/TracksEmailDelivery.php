<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Enums\EmailLogStatus;
use App\Models\EmailLog;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared plumbing for any queued Mailable that should be tracked in the
 * EmailLog table: tags the outgoing message with a stable Message-ID (for
 * the mail provider's delivery webhook to correlate back to) and an
 * X-Email-Log-Id header (for our own MessageSent listener), and marks the
 * log row Failed once queue retries are exhausted.
 *
 * A Mailable using this trait must declare its own public ?int $emailLogId
 * property (typically via constructor promotion) and set it via
 * App\Services\EmailLogger::track() before being queued.
 */
trait TracksEmailDelivery
{
    /**
     * Done via the headers() hook — plain serializable data — rather than
     * withSymfonyMessage(), whose closure can't survive queue job serialization.
     */
    public function headers(): Headers
    {
        if (! $this->emailLogId) {
            return new Headers;
        }

        $log = EmailLog::find($this->emailLogId);

        return new Headers(
            messageId: $log?->message_id ?? $log?->generateMessageId(),
            text: ['X-Email-Log-Id' => (string) $this->emailLogId],
        );
    }

    /**
     * Handle a job failure after all retries have been exhausted.
     */
    public function failed(Throwable $exception): void
    {
        Log::error(static::class.' failed to deliver after retries', [
            'email_log_id' => $this->emailLogId,
            'error' => $exception->getMessage(),
        ]);

        if ($this->emailLogId) {
            EmailLog::where('id', $this->emailLogId)->update([
                'status' => EmailLogStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
