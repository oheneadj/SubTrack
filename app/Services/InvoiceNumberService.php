<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;

class InvoiceNumberService
{
    /**
     * Build the next invoice number for the current year.
     *
     * Based on the highest sequence number actually in use, not a row
     * count — Invoice uses SoftDeletes, so a plain count already excludes
     * soft-deleted invoices by default, silently producing a colliding,
     * already-taken number the moment any invoice for the year has ever
     * been deleted.
     */
    public function generate(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV');
        $year = now()->year;

        $maxSequence = Invoice::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->get(['invoice_number'])
            ->map(fn (Invoice $i) => (int) substr($i->invoice_number, strrpos($i->invoice_number, '-') + 1))
            ->max() ?? 0;

        $sequence = str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }
}
