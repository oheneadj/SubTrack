<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft   = 'Draft';
    case Sent    = 'Sent';
    case Paid    = 'Paid';
    case Overdue = 'Overdue';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match($this) {
            self::Draft   => 'neutral',
            self::Sent    => 'info',
            self::Paid    => 'success',
            self::Overdue => 'error',
        };
    }
}
