<?php

namespace App\Observers;

use App\Enums\ActivityEventType;
use App\Models\Client;
use App\Models\DashboardActivityLog;

class ClientObserver
{
    public function created(Client $client): void
    {
        DashboardActivityLog::record(
            ActivityEventType::ClientCreated,
            "New client added: {$client->name}",
            $client->id
        );
    }
}
