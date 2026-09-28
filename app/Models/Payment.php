<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentRecordStatus;
use App\Traits\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property string|null $notes
 * @property string|null $void_reason
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
        'notes',
        'void_reason',
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

    /** @return HasMany<Receipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    /** True once a still-valid receipt already documents this specific payment. */
    public function hasReceipt(): bool
    {
        return $this->receipts()->whereNull('invalidated_at')->exists();
    }

    /**
     * A manually-recorded payment can be voided any time it's still
     * Succeeded — voiding always preserves the original record (it never
     * overwrites the amount), so it's safe regardless of receipts already
     * issued.
     */
    public function isVoidable(): bool
    {
        return $this->gateway === 'manual' && $this->status === PaymentRecordStatus::Succeeded;
    }

    /**
     * A manual payment's amount can only be edited in place — rather than
     * voided and re-recorded — within a configurable window after it was
     * recorded (Setting `payment_edit_window_hours`, default 24) and only
     * while no receipt has been issued for *this specific payment*. Once
     * either of those is no longer true, a receipt may already document
     * the original amount, so editing in place would silently invalidate
     * it; voiding and recording a fresh payment is the safe path instead.
     */
    public function isEditable(): bool
    {
        if (! $this->isVoidable()) {
            return false;
        }

        $windowHours = (int) Setting::get('payment_edit_window_hours', 24);
        if ($this->created_at->diffInHours(CarbonImmutable::now()) >= $windowHours) {
            return false;
        }

        return ! $this->hasReceipt();
    }
}
