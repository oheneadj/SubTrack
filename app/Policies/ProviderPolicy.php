<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

/**
 * Authorization policy for Provider records.
 * All authenticated users can manage providers — this app is single-tenant.
 */
class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Provider $provider): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Provider $provider): bool
    {
        return true;
    }

    public function delete(User $user, Provider $provider): bool
    {
        return true;
    }
}
