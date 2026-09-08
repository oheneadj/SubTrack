<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityEventType;
use App\Enums\SubscriptionStatus;
use App\Models\DashboardActivityLog;
use App\Models\Subscription;

/** Records subscription lifecycle events to the dashboard activity feed. */
class SubscriptionObserver
{
    /** Log a new subscription being added. */
    public function created(Subscription $subscription): void
    {
        $name = $subscription->domain_name ?: $subscription->service_type->label();

        DashboardActivityLog::record(
            ActivityEventType::SubscriptionCreated,
            "New subscription added: {$name}",
            $subscription->effectiveClientIdWithoutLoading(),
            ['subscription_id' => $subscription->id]
        );
    }

    /** Log a subscription entering the "expiring soon" or "expired" state. */
    public function updated(Subscription $subscription): void
    {
        if (! $subscription->wasChanged('status')) {
            return;
        }

        $name = $subscription->domain_name ?: $subscription->service_type->label();
        $clientId = $subscription->effectiveClientIdWithoutLoading();

        if ($subscription->status === SubscriptionStatus::Expiring) {
            DashboardActivityLog::record(
                ActivityEventType::SubscriptionExpiring,
                "{$name} is expiring soon ({$subscription->days_until_expiry} days left)",
                $clientId,
                ['subscription_id' => $subscription->id, 'days_left' => $subscription->days_until_expiry]
            );
        }

        if ($subscription->status === SubscriptionStatus::Expired) {
            DashboardActivityLog::record(
                ActivityEventType::SubscriptionExpired,
                "{$name} has expired",
                $clientId,
                ['subscription_id' => $subscription->id]
            );
        }
    }
}
