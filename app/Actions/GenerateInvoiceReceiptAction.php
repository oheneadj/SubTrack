<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentRecordStatus;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\ReceiptNumberService;
use App\Services\ReceiptPdfService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Generates a receipt for one specific succeeded payment, and renders it
 * to PDF. Each payment gets its own receipt — two separate payments on
 * the same invoice (e.g. two partial payments made before either was
 * receipted) must never merge into a single receipt for their combined
 * total, since a receipt is proof of one specific transaction. Unlike
 * GenerateReceiptAction (subscription payments), this never emails
 * automatically — the admin decides separately whether to download it or
 * send it to the client (see ReceiptIndex).
 */
class GenerateInvoiceReceiptAction
{
    public function __construct(
        private readonly ReceiptNumberService $numberService,
        private readonly ReceiptPdfService $pdfService,
    ) {}

    /**
     * @throws RuntimeException if the payment isn't succeeded, has no
     *                          client to issue to, or already has a receipt
     */
    public function execute(Payment $payment, ?string $notes = null): Receipt
    {
        if ($payment->status !== PaymentRecordStatus::Succeeded) {
            throw new RuntimeException('Cannot generate a receipt for a payment that has not succeeded.');
        }

        if ($payment->hasReceipt()) {
            throw new RuntimeException('This payment already has a receipt.');
        }

        $invoice = $payment->invoice()->first();
        if (! $invoice->client) {
            throw new RuntimeException('Cannot generate a receipt for an invoice with no linked client.');
        }

        return DB::transaction(function () use ($invoice, $payment, $notes) {
            $receipt = Receipt::create([
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'client_id' => $invoice->client_id,
                'receipt_number' => $this->numberService->generate(),
                'amount_usd' => $payment->amount,
                'issued_date' => CarbonImmutable::now(),
                'notes' => $notes,
            ]);

            $this->pdfService->generate($receipt);

            return $receipt;
        });
    }
}
