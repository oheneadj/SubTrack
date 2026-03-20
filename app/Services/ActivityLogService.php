<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log a manual activity.
     */
    public function log(
        string $action,
        ?string $description = null,
        ?object $subject = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'      => Auth::id(),
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject ? $subject->getKey() : null,
            'description'  => $description,
            'properties'   => $properties,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
        ]);
    }

    /**
     * Shortcut for logging auth events.
     */
    public function logAuth(string $event, ?string $description = null): void
    {
        $this->log("auth.{$event}", $description);
    }

    /**
     * Shortcut for logging mail events.
     */
    public function logMail(string $type, string $recipient, ?string $description = null, array $metadata = []): void
    {
        $this->log('mail.sent', $description, null, array_merge([
            'type'      => $type,
            'recipient' => $recipient,
        ], $metadata));
    }
}
