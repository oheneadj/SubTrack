<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPublicUlid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $ulid
 * @property int $client_id
 * @property string $project_name
 * @property string|null $description
 * @property-read Client|null $client
 * @property-read Collection<int, Subscription> $subscriptions
 */
class Project extends Model
{
    use HasFactory, HasPublicUlid, LogsActivity, SoftDeletes;

    /** Name used for the fallback project auto-provisioned per client. */
    public const UNRELATED_NAME = 'Unrelated';

    protected $fillable = ['client_id', 'project_name', 'description'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Deleting a project cascades a soft-delete to its subscriptions — they
     * represent active scope tied to this project, not historical money
     * records, so leaving them behind (still active, still renewable) once
     * their project is gone would be an inconsistent, orphaned state.
     * Invoices raised against this project are left untouched: they're
     * already-billed history, and already resolve their project/client
     * defensively wherever they're displayed.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $project): void {
            $project->subscriptions()->get()->each->delete();
        });

        // Mirrors the deleting cascade above.
        static::restoring(function (self $project): void {
            $project->subscriptions()->onlyTrashed()->get()->each->restore();
        });
    }

    /**
     * Get (or create) the client's catch-all "Unrelated" project — used when a
     * subscription is created without picking a real project, so it still has
     * something to group/filter by instead of a bare null.
     */
    public static function unrelatedFor(Client $client): self
    {
        return static::firstOrCreate([
            'client_id' => $client->id,
            'project_name' => self::UNRELATED_NAME,
        ]);
    }
}
