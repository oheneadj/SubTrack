<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

/**
 * Authorization policy for Subscription records.
 * All authenticated users can manage subscriptions — this app is single-tenant.
 */
class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return true;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return true;
    }
}
