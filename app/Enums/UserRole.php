<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case User       = 'user';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin => 'Super Admin',
            self::User       => 'User',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::SuperAdmin => 'primary',
            self::User       => 'neutral',
        };
    }
}
