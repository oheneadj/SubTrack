<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;

class InvoiceNumberService
{
    public function generate(): string
    {
        $year = now()->year;
        $count = Invoice::whereYear('created_at', $year)->count();
        $sequence = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "INV-{$year}-{$sequence}";
    }
}
