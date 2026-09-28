<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\ReceiptNumberService;
use App\Services\ReceiptPdfService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Generates a receipt covering everything paid on an invoice so far, and
 * renders it to PDF. Unlike GenerateReceiptAction (subscription payments),
 * this never emails automatically — the admin decides separately whether
 * to download it or send it to the client (see ReceiptIndex).
 */
class GenerateInvoiceReceiptAction
{
    public function __construct(
        private readonly ReceiptNumberService $numberService,
        private readonly ReceiptPdfService $pdfService,
    ) {}

    /**
     * @throws \RuntimeException if the invoice has no client, or nothing has been paid yet
     */
    public function execute(Invoice $invoice, ?string $notes = null): Receipt
    {
        if (! $invoice->client) {
            throw new \RuntimeException('Cannot generate a receipt for an invoice with no linked client.');
        }

        if ($invoice->amount_paid <= 0) {
            throw new \RuntimeException('Cannot generate a receipt before any payment has been received.');
        }

        return DB::transaction(function () use ($invoice, $notes) {
            $receipt = Receipt::create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'receipt_number' => $this->numberService->generate(),
                'amount_usd' => $invoice->amount_paid,
                'issued_date' => CarbonImmutable::now(),
                'notes' => $notes,
            ]);

            $this->pdfService->generate($receipt);

            return $receipt;
        });
    }
}
