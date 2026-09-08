<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records every processed provider webhook event by its unique ID, so a
 * duplicate delivery of the same event (from any gateway) is never
 * processed twice — even if it doesn't touch a Payment row we can check
 * status on directly (e.g. a differently-typed event on the same session).
 *
 * @property int $id
 * @property string $gateway
 * @property string $event_id
 */
class WebhookEvent extends Model
{
    /** @var list<string> */
    protected $fillable = ['gateway', 'event_id'];
}
