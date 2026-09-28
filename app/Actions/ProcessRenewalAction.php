<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Exceptions\RenewalAlreadyProcessedException;
use App\Exceptions\RenewalNotPaidException;
use App\Models\Renewal;

/**
 * Applies an already-paid renewal to its subscription — rolls the expiry
 * date to Renewal::new_expiry_date, keeps renewal_cost_usd in sync if the
 * provider's price changed, and marks the renewal processed. The final
 * step of take payment -> generate receipt -> process renewal.
 */
class ProcessRenewalAction
{
    /**
     * @throws RenewalNotPaidException if the renewal hasn't been paid yet
     * @throws RenewalAlreadyProcessedException if it's already been processed
     */
    public function execute(Renewal $renewal): Renewal
    {
        if (! $renewal->isAwaitingProcessing()) {
            if ($renewal->renewal_confirmed_date) {
                throw new RenewalAlreadyProcessedException;
            }

            throw new RenewalNotPaidException;
        }

        $subscription = $renewal->subscription;
        $newExpiry = $renewal->new_expiry_date ?? $subscription->expiry_date;

        $updateData = [
            'expiry_date' => $newExpiry,
            'status' => SubscriptionStatus::Active,
        ];

        // If the provider changed their price, keep the subscription in sync.
        if ($renewal->provider_cost_usd !== $subscription->renewal_cost_usd) {
            $updateData['renewal_cost_usd'] = $renewal->provider_cost_usd;
        }

        $subscription->update($updateData);

        $renewal->update(['renewal_confirmed_date' => now()]);

        return $renewal->fresh();
    }
}
