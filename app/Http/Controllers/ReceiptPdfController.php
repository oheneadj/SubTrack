<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\ReceiptPdfService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a receipt PDF inline (opened as a real browser navigation, in a
 * new tab), as opposed to Livewire's file-download actions which always
 * force a save-as regardless of the response's Content-Disposition header.
 */
class ReceiptPdfController extends Controller
{
    public function view(Receipt $receipt, ReceiptPdfService $pdfService): StreamedResponse|BinaryFileResponse
    {
        if (! $receipt->pdf_path || ! Storage::exists('public/'.$receipt->pdf_path)) {
            $pdfService->generate($receipt);
        }

        return Storage::response('public/'.$receipt->pdf_path, $receipt->receipt_number.'.pdf');
    }
}
