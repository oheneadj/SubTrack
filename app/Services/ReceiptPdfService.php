<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Receipt;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/** Renders a receipt to PDF and stores it on the public disk, mirroring InvoicePdfService. */
class ReceiptPdfService
{
    /** Render the receipt PDF, store it, and persist its path on the record. Returns the stored path. */
    public function generate(Receipt $receipt): string
    {
        $receipt->load(['client', 'subscription']);
        $settings = Setting::getAllAsArray();

        $pdf = Pdf::loadView('pdf.receipt', compact('receipt', 'settings'))
            ->setPaper('a4', 'portrait');

        $path = "receipts/{$receipt->receipt_number}.pdf";
        Storage::put("public/{$path}", $pdf->output());
        $receipt->update(['pdf_path' => $path]);

        return $path;
    }
}
