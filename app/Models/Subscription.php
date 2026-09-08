<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Traits\HasPublicUlid;
use App\Traits\LogsActivity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $ulid
 * @property int|null $client_id
 * @property int|null $project_id
 * @property int|null $provider_id
 * @property ServiceType $service_type
 * @property SubscriptionRenewalType $renewal_type
 * @property string $domain_name
 * @property SubscriptionStatus $status
 * @property string|null $notes
 * @property int $purchase_cost_usd
 * @property int $renewal_cost_usd
 * @property float|null $markup_percentage
 * @property int $client_renewal_cost_usd
 * @property string $formatted_purchase_cost_usd
 * @property string $formatted_renewal_cost_usd
 * @property string $formatted_client_renewal_cost_usd
 * @property CarbonImmutable $purchase_date
 * @property CarbonImmutable $expiry_date
 * @property int $days_until_expiry
 * @property string $traffic_light
 * @property-read Client|null $client
 * @property-read Project|null $project
 * @property-read Provider|null $provider
 * @property-read Collection<int, Renewal> $renewals
 */
class Subscription extends Model
{
    use HasFactory, HasPublicUlid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'client_id', 'project_id', 'service_type', 'renewal_type', 'provider_id', 'domain_name',
        'purchase_date', 'expiry_date', 'purchase_cost_usd',
        'renewal_cost_usd', 'markup_percentage', 'status', 'notes',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date' => 'date',
        'service_type' => ServiceType::class,
        'renewal_type' => SubscriptionRenewalType::class,
        'status' => SubscriptionStatus::class,
        'purchase_cost_usd' => 'integer',
        'renewal_cost_usd' => 'integer',
        'markup_percentage' => 'decimal:2',
    ];

    public function getFormattedPurchaseCostUsdAttribute(): string
    {
        return '$'.number_format($this->purchase_cost_usd / 100, 2);
    }

    public function getFormattedRenewalCostUsdAttribute(): string
    {
        return '$'.number_format($this->renewal_cost_usd / 100, 2);
    }

    /** Returns the renewal cost with markup applied, in cents. */
    public function getClientRenewalCostUsdAttribute(): int
    {
        $markup = (float) ($this->markup_percentage ?? 0);

        return (int) round($this->renewal_cost_usd * (1 + $markup / 100));
    }

    public function getFormattedClientRenewalCostUsdAttribute(): string
    {
        return '$'.number_format($this->client_renewal_cost_usd / 100, 2);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Returns the client regardless of whether the subscription is linked
     * directly (client_id) or through a project (project.client).
     */
    public function getEffectiveClientAttribute(): ?Client
    {
        return $this->client ?? $this->project?->client;
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    /** @return HasMany<Receipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    // Scopes
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('expiry_date', '<=', now()->addDays(7))
            ->where('status', '!=', SubscriptionStatus::Cancelled);
    }

    public function scopeWarning(Builder $query): Builder
    {
        return $query->whereBetween('expiry_date', [now()->addDays(8), now()->addDays(30)])
            ->where('status', '!=', SubscriptionStatus::Cancelled);
    }

    public function scopeHealthy(Builder $query): Builder
    {
        return $query->where('expiry_date', '>', now()->addDays(30))
            ->where('status', SubscriptionStatus::Active);
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    public function getTrafficLightAttribute(): string
    {
        if ($this->days_until_expiry <= 7) {
            return 'critical';
        }
        if ($this->days_until_expiry <= 30) {
            return 'warning';
        }

        return 'healthy';
    }
}
