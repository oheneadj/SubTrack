<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Adds a public-facing ULID to any model, keeping the internal bigint PK intact.
 *
 * The `ulid` column is auto-generated on creation and used for route model
 * binding — so URLs and external references never expose the integer PK.
 *
 * @property string $ulid
 */
trait HasPublicUlid
{
    /**
     * Auto-generate a ULID before the model is persisted for the first time.
     */
    protected static function bootHasPublicUlid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('ulid'))) {
                $model->setAttribute('ulid', (string) Str::ulid());
            }
        });
    }

    /**
     * Use the ulid column for route model binding instead of the integer PK.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
