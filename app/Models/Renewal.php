<?php

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
        'due_date'                 => 'date',
        'payment_received_date'    => 'date',
        'renewal_confirmed_date'   => 'date',
        'payment_status'           => PaymentStatus::class,
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getMarginAttribute(): float
    {
        return (float) ($this->client_cost_usd - $this->provider_cost_usd);
    }
}
