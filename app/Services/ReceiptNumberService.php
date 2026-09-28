<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Receipt;
use App\Models\Setting;

/** Generates sequential, year-scoped receipt numbers (e.g. "RCT-2026-001"). */
class ReceiptNumberService
{
    /** Build the next receipt number for the current year. */
    public function generate(): string
    {
        $prefix = Setting::get('receipt_prefix', 'RCT');
        $year = now()->year;
        $count = Receipt::whereYear('created_at', $year)->count();
        $sequence = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }
}
