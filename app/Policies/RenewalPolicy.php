<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Renewal;
use App\Models\User;

/**
 * Authorization policy for Renewal records.
 * All authenticated users can manage renewals — this app is single-tenant.
 */
class RenewalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Renewal $renewal): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Renewal $renewal): bool
    {
        return true;
    }

    public function delete(User $user, Renewal $renewal): bool
    {
        return true;
    }
}
