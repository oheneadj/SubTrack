<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single-use magic link token for client portal authentication.
 *
 * @property int $id
 * @property int $client_id
 * @property string $token
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $used_at
 */
class ClientAuthToken extends Model
{
    /** @var list<string> */
    protected $fillable = ['client_id', 'token', 'expires_at', 'used_at'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
        ];
    }

    /** True when the token has not been used and has not expired. */
    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
