<?php

namespace App\Models;

use App\Enums\ActivityEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardActivityLog extends Model
{
    protected $fillable = ['client_id', 'event_type', 'description', 'meta'];

    protected $casts = [
        'event_type' => ActivityEventType::class,
        'meta'       => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Helper to create a log entry from anywhere in the app.
     */
    public static function record(
        ActivityEventType $type,
        string $description,
        ?int $clientId = null,
        array $meta = []
    ): self {
        return static::create([
            'event_type'  => $type,
            'description' => $description,
            'client_id'   => $clientId,
            'meta'        => $meta,
        ]);
    }
}
