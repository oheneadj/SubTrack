<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentRecordStatus;
use App\Traits\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single payment attempt against an invoice via a specific gateway.
 *
 * @property int $id
 * @property string $ulid
 * @property int $invoice_id
 * @property string $gateway
 * @property string|null $gateway_payment_id
 * @property string $method
 * @property int $amount
 * @property string $currency
 * @property PaymentRecordStatus $status
 * @property array<string, mixed>|null $gateway_response
 * @property CarbonImmutable|null $paid_at
 * @property string $formatted_amount
 */
class Payment extends Model
{
    use HasPublicUlid;

    /** @var list<string> */
    protected $fillable = [
        'invoice_id',
        'gateway',
        'gateway_payment_id',
        'method',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'paid_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => PaymentRecordStatus::class,
            'gateway_response' => 'array',
            'paid_at' => 'immutable_datetime',
            'amount' => 'integer',
        ];
    }

    /** Amount formatted for display (e.g. "$12.50"). */
    public function getFormattedAmountAttribute(): string
    {
        return '$'.number_format($this->amount / 100, 2);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
