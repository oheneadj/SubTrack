<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailLogStatus;
use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A record of a single outbound email send attempt — one row per recipient,
 * grouped by `batch_id` (every send from Direct Mailer, individual or bulk,
 * gets a batch, even a batch of one).
 *
 * Subject/body are snapshots of what was actually sent, not references to
 * live template data, so a batch's history stays accurate even if the
 * client, subscription, or business settings change afterward.
 *
 * @property int $id
 * @property string $ulid
 * @property string $batch_id
 * @property int|null $user_id
 * @property int|null $client_id
 * @property string $mailable_class
 * @property string|null $message_id
 * @property string $to_email
 * @property string|null $to_name
 * @property string $subject
 * @property string $body
 * @property array|null $context
 * @property EmailLogStatus $status
 * @property string|null $error_message
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $bounced_at
 * @property Carbon|null $opened_at
 * @property Carbon|null $clicked_at
 * @property-read User|null $user
 * @property-read Client|null $client
 * @property-read Collection<int, EmailLogEvent> $events
 */
class EmailLog extends Model
{
    use HasPublicUlid;

    /** @var list<string> */
    protected $fillable = [
        'batch_id', 'user_id', 'client_id', 'mailable_class', 'message_id',
        'to_email', 'to_name', 'subject', 'body', 'context',
        'status', 'error_message', 'sent_at', 'delivered_at', 'bounced_at',
        'opened_at', 'clicked_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'status' => EmailLogStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'bounced_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return HasMany<EmailLogEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(EmailLogEvent::class)->latest('occurred_at');
    }

    /**
     * The Message-ID header value we tag this email's outgoing message with,
     * so an incoming provider webhook (delivered/bounced/etc.) can be
     * correlated back to this row without exposing the raw internal id.
     */
    public function generateMessageId(): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return "{$this->ulid}@{$host}";
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', EmailLogStatus::Failed);
    }

    /**
     * Anything worth retrying — genuinely failed, bounced, or blocked.
     * Excludes spam complaints: resending to someone who reported the
     * message as spam would be bad practice, not a delivery fix.
     */
    public function scopeResendable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            EmailLogStatus::Failed,
            EmailLogStatus::Bounced,
            EmailLogStatus::Blocked,
        ]);
    }

    public function scopeForBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }
}
