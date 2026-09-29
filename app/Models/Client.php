<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPublicUlid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $company_name
 * @property-read Collection<int, Project> $projects
 * @property-read Collection<int, Subscription> $subscriptions
 */
class Client extends Model
{
    use HasFactory, HasPublicUlid, LogsActivity, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'company_name'];

    /**
     * Deleting a client cascades a soft-delete to its projects (whose own
     * deleting hook cascades further to their subscriptions) and any
     * subscription attached directly to this client with no project —
     * active scope that shouldn't keep renewing or showing up as live once
     * its client is gone. Invoices/Renewals/Payments/Receipts are left
     * untouched: they're already-billed financial history, not active
     * scope, and already resolve their client/subscription defensively
     * (?->name ?? 'Unknown Client') everywhere they're displayed.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $client): void {
            $client->projects()->get()->each->delete();
            $client->directSubscriptions()->get()->each->delete();
        });

        // Mirrors the deleting cascade above: restoring a client restores
        // every project (which cascades to its own subscriptions) and
        // direct subscription that's currently trashed, rather than
        // leaving the client "back" but its scope still gone.
        static::restoring(function (self $client): void {
            $client->projects()->onlyTrashed()->get()->each->restore();
            $client->directSubscriptions()->onlyTrashed()->get()->each->restore();
        });
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** Subscriptions attached directly to this client (no project). */
    public function directSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** Subscriptions linked via this client's projects. */
    public function projectSubscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(Subscription::class, Project::class);
    }

    /**
     * All subscriptions for this client — both direct and via projects.
     * Returns an Eloquent collection merged from both relationships.
     * Use directSubscriptions() or projectSubscriptions() for queryable scopes.
     */
    public function allSubscriptions(): SupportCollection
    {
        return $this->directSubscriptions->merge($this->projectSubscriptions);
    }

    // Scopes
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('company_name', 'like', "%{$term}%");
    }
}
