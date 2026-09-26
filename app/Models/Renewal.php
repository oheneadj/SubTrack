<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Renewal extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'subscription_id', 'invoice_id', 'due_date',
        'provider_cost_usd', 'client_cost_usd', 'payment_status',
        'payment_received_date', 'renewal_confirmed_date', 'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_received_date' => 'date',
        'renewal_confirmed_date' => 'date',
        'payment_status' => PaymentStatus::class,
        'provider_cost_usd' => 'integer',
        'client_cost_usd' => 'integer',
    ];

    public function getFormattedProviderCostUsdAttribute(): string
    {
        return '$'.number_format($this->provider_cost_usd / 100, 2);
    }

    public function getFormattedClientCostUsdAttribute(): string
    {
        return '$'.number_format($this->client_cost_usd / 100, 2);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Returns margin in cents (integer minor units). */
    public function getMarginAttribute(): int
    {
        return $this->client_cost_usd - $this->provider_cost_usd;
    }

    public function getFormattedMarginAttribute(): string
    {
        return '$'.number_format($this->margin / 100, 2);
    }
}
