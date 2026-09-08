<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPublicUlid;
use App\Traits\LogsActivity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A generated, sent (or sendable) proof-of-payment for a subscription.
 *
 * Receipts are immutable historical records — amount and client are
 * captured at issue time and must never be recomputed from live
 * Subscription/Client data on redisplay, even if either is edited later.
 *
 * @property int $id
 * @property string $ulid
 * @property int $subscription_id
 * @property int $client_id
 * @property string $receipt_number
 * @property int $amount_usd
 * @property CarbonImmutable $issued_date
 * @property string|null $notes
 * @property string|null $pdf_path
 * @property string $formatted_amount_usd
 * @property-read Subscription $subscription
 * @property-read Client $client
 */
class Receipt extends Model
{
    use HasFactory, HasPublicUlid, LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'subscription_id', 'client_id', 'receipt_number',
        'amount_usd', 'issued_date', 'notes', 'pdf_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'amount_usd' => 'integer',
        ];
    }

    /** Amount formatted for display (e.g. "$12.50"). */
    public function getFormattedAmountUsdAttribute(): string
    {
        return '$'.number_format($this->amount_usd / 100, 2);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
