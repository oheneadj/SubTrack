<?php

namespace App\Models;

use App\Enums\ServiceType;
use App\Enums\SubscriptionStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'project_id', 'service_type', 'provider_id', 'domain_name',
        'purchase_date', 'expiry_date', 'purchase_cost_usd',
        'renewal_cost_usd', 'status',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date'   => 'date',
        'service_type'  => ServiceType::class,
        'status'        => SubscriptionStatus::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    // Scopes
    public function scopeCritical($query)
    {
        return $query->where('expiry_date', '<=', now()->addDays(7))
                     ->where('status', '!=', SubscriptionStatus::Cancelled);
    }

    public function scopeWarning($query)
    {
        return $query->whereBetween('expiry_date', [now()->addDays(8), now()->addDays(30)])
                     ->where('status', '!=', SubscriptionStatus::Cancelled);
    }

    public function scopeHealthy($query)
    {
        return $query->where('expiry_date', '>', now()->addDays(30))
                     ->where('status', SubscriptionStatus::Active);
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getTrafficLightAttribute(): string
    {
        if ($this->days_until_expiry <= 7)  return 'critical';
        if ($this->days_until_expiry <= 30) return 'warning';
        return 'healthy';
    }
}
