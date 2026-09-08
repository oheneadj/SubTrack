<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory, HasPublicUlid, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name', 'website', 'support_email'];

    /** Frees the unique `name` value on soft delete so it can be reused by a new provider. */
    protected static function booted(): void
    {
        static::deleting(function (self $provider): void {
            $provider->forceFill(['name' => "{$provider->name}-deleted-{$provider->id}"])->saveQuietly();
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
