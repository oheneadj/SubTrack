<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->load(['client', 'items']);
        $settings = Setting::getAllAsArray();

        // Note: pdf.invoice view will be created in Phase 8
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'settings'))
                   ->setPaper('a4', 'portrait');

        $path = "invoices/{$invoice->invoice_number}.pdf";
        Storage::put("public/{$path}", $pdf->output());
        $invoice->update(['pdf_path' => $path]);

        return $path;
    }
}
