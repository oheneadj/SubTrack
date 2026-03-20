<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending  = 'Pending';
    case Invoiced = 'Invoiced';
    case Paid     = 'Paid';
    case Renewed  = 'Renewed';
    case Lapsed   = 'Lapsed';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match($this) {
            self::Pending  => 'neutral',
            self::Invoiced => 'info',
            self::Paid     => 'success',
            self::Renewed  => 'primary',
            self::Lapsed   => 'error',
        };
    }
}
