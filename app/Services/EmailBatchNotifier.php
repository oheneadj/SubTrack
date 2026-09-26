<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailLogStatus;
use App\Mail\GenericClientMail;
use App\Models\EmailLog;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Cache;

/**
 * A Direct Mailer batch is sent, then finishes asynchronously as its queued
 * jobs actually run — the person who clicked "Send" has long since moved on
 * by the time delivery succeeds or fails. This notifies them (via the
 * notification bell) once every email in the batch has left the Queued
 * state, so they don't have to keep checking the Email Log manually.
 *
 * Scoped to GenericClientMail (Direct Mailer) only — the other mailables
 * (invoices, invites, receipts, reminders) are always a batch of one sent
 * as a direct consequence of the admin's own action, so a second
 * notification on top of that would just be noise.
 */
class EmailBatchNotifier
{
    public function notifyIfComplete(EmailLog $log): void
    {
        if ($log->mailable_class !== GenericClientMail::class || ! $log->user_id) {
            return;
        }

        $batchId = $log->batch_id;

        // Only act on the update that actually completes the batch, and only once.
        $stillQueued = EmailLog::forBatch($batchId)->where('status', EmailLogStatus::Queued)->exists();
        if ($stillQueued) {
            return;
        }

        if (! Cache::add("email-batch-notified:{$batchId}", true, now()->addHour())) {
            return;
        }

        $user = User::find($log->user_id);
        if (! $user) {
            return;
        }

        $sentCount = EmailLog::forBatch($batchId)->whereIn('status', [EmailLogStatus::Sent, EmailLogStatus::Delivered])->count();
        $failedCount = EmailLog::forBatch($batchId)->whereIn('status', [
            EmailLogStatus::Failed, EmailLogStatus::Bounced, EmailLogStatus::Blocked, EmailLogStatus::Complained,
        ])->count();

        $title = $failedCount > 0 ? 'Some emails could not be sent' : 'Your emails were sent successfully';

        $message = $failedCount > 0
            ? $this->describeCount($sentCount, 'went out fine').', but '.$this->describeCount($failedCount, 'did not go through').'. Tap to see what happened.'
            : 'Everyone on your list received it — '.$this->describeCount($sentCount, 'in total').'.';

        $user->notify(new SystemNotification(
            title: $title,
            message: $message,
            actionUrl: route('email-logs.show', $batchId),
        ));
    }

    private function describeCount(int $count, string $suffix): string
    {
        $people = $count === 1 ? '1 email' : "{$count} emails";

        return "{$people} {$suffix}";
    }
}
