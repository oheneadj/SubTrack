<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active    = 'Active';
    case Expiring  = 'Expiring';
    case Expired   = 'Expired';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Active',
            self::Expiring  => 'Expiring Soon',
            self::Expired   => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Active    => 'success',
            self::Expiring  => 'warning',
            self::Expired   => 'error',
            self::Cancelled => 'neutral',
        };
    }
}
