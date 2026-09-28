<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'Draft';
    case Sent = 'Sent';
    case PartiallyPaid = 'Partially Paid';
    case Paid = 'Paid';
    case Overdue = 'Overdue';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Sent => 'info',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
            self::Overdue => 'error',
        };
    }
}
