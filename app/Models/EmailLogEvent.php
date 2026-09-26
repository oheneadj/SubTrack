<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single raw webhook event reported by the mail provider (Brevo) for an
 * EmailLog — delivered, bounced, blocked, spam complaint, opened, clicked.
 * The full raw payload is kept for traceability, per the project's rule
 * that third-party API responses are logged in full to a dedicated table.
 *
 * @property int $id
 * @property int $email_log_id
 * @property string $event
 * @property array $payload
 * @property Carbon|null $occurred_at
 * @property-read EmailLog $emailLog
 */
class EmailLogEvent extends Model
{
    /** @var list<string> */
    protected $fillable = ['email_log_id', 'event', 'payload', 'occurred_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EmailLog, $this> */
    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }
}
