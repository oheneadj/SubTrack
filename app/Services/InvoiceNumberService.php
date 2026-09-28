<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;

class InvoiceNumberService
{
    public function generate(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV');
        $year = now()->year;
        $count = Invoice::whereYear('created_at', $year)->count();
        $sequence = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }
}
