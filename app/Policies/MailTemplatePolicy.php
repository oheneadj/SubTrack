<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MailTemplate;
use App\Models\User;

/**
 * Authorization policy for MailTemplate records.
 * Only super admins can view or manage mail templates.
 */
class MailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, MailTemplate $mailTemplate): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, MailTemplate $mailTemplate): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, MailTemplate $mailTemplate): bool
    {
        return $user->isSuperAdmin();
    }
}
