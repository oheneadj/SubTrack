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
