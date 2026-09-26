<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Authorization policy for User management.
 * Only super admins can view or manage other users.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        // Super admins cannot delete themselves
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }
}
