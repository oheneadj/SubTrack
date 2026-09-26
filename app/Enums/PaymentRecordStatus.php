<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of an individual payment attempt against an invoice.
 * Distinct from PaymentStatus (which tracks renewal-level payment state).
 */
enum PaymentRecordStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /** Human-readable label for display. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    /** Tailwind colour classes for badge display. */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Succeeded => 'bg-green-100 text-green-700',
            self::Failed => 'bg-red-100 text-red-700',
            self::Refunded => 'bg-slate-100 text-slate-600',
        };
    }
}
