<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityEventType;
use App\Models\DashboardActivityLog;
use App\Models\Renewal;
use App\Models\Subscription;

/** Records renewal confirmations to the dashboard activity feed. */
class RenewalObserver
{
    /** Log a renewal being confirmed. */
    public function created(Renewal $renewal): void
    {
        // Fetch fresh (rather than $renewal->subscription) to avoid lazy-loading
        // a relation that was never eager loaded on this instance.
        $subscription = Subscription::find($renewal->subscription_id);

        if (! $subscription) {
            return;
        }

        $name = $subscription->domain_name ?: $subscription->service_type->label();

        DashboardActivityLog::record(
            ActivityEventType::RenewalConfirmed,
            "Renewal confirmed for {$name}",
            $subscription->effectiveClientIdWithoutLoading(),
            ['subscription_id' => $subscription->id, 'renewal_id' => $renewal->id]
        );
    }
}
