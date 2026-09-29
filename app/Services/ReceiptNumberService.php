<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Receipt;
use App\Models\Setting;

/** Generates sequential, year-scoped receipt numbers (e.g. "RCT-2026-001"). */
class ReceiptNumberService
{
    /**
     * Build the next receipt number for the current year.
     *
     * Based on the highest sequence number actually in use, not a row
     * count — a plain count silently produces a colliding, already-taken
     * number the moment any receipt for the year has ever been deleted
     * (e.g. via `receipts:cleanup-legacy`), since the count no longer
     * matches the highest sequence still on record.
     */
    public function generate(): string
    {
        $prefix = Setting::get('receipt_prefix', 'RCT');
        $year = now()->year;

        $maxSequence = Receipt::where('receipt_number', 'like', "{$prefix}-{$year}-%")
            ->get(['receipt_number'])
            ->map(fn (Receipt $r) => (int) substr($r->receipt_number, strrpos($r->receipt_number, '-') + 1))
            ->max() ?? 0;

        $sequence = str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }
}
