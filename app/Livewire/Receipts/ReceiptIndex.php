<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\GenerateInvoiceReceiptAction;
use App\Mail\ReceiptMail;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\EmailLogger;
use App\Services\ReceiptPdfService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lists every receipt issued app-wide — for subscription renewals and for
 * invoice payments alike — with who paid, what it was for, and actions to
 * view, download, or (re)send it. Optionally scoped to a single invoice
 * via the `invoice` query string param, e.g. linked from the Invoices list.
 */
class ReceiptIndex extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public string $invoice = '';

    /** Notes entered before generating a new receipt for the scoped invoice. */
    public string $newReceiptNotes = '';

    #[Computed]
    public function scopedInvoice(): ?Invoice
    {
        if (! $this->invoice) {
            return null;
        }

        return Invoice::with('client')->where('ulid', $this->invoice)->first();
    }

    #[Computed]
    public function receipts()
    {
        return Receipt::with(['client', 'invoice', 'subscription'])
            ->when($this->scopedInvoice, fn ($query) => $query->where('invoice_id', $this->scopedInvoice->id))
            ->when($this->search, function ($query) {
                $query->where('receipt_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'));
            })
            ->latest('issued_date')
            ->paginate(15);
    }

    /** Generates a new receipt covering everything paid so far on the scoped invoice. */
    public function generateReceipt(GenerateInvoiceReceiptAction $action): void
    {
        if (! $this->scopedInvoice) {
            return;
        }

        try {
            $action->execute($this->scopedInvoice, $this->newReceiptNotes ?: null);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->newReceiptNotes = '';
        unset($this->receipts);

        session()->flash('success', 'Receipt generated. You can download it or send it to the client below.');
    }

    /** Emails the receipt PDF to the client it was issued for. */
    public function sendReceipt(string $receiptUlid, EmailLogger $emailLogger): void
    {
        $receipt = Receipt::with('client')->where('ulid', $receiptUlid)->firstOrFail();

        $mail = new ReceiptMail($receipt);
        $emailLogger->track(
            $mail,
            $receipt->client->email,
            $receipt->client->name,
            clientId: $receipt->client_id,
            context: ['receipt_id' => $receipt->id],
        );

        Mail::to($receipt->client->email)->queue($mail);

        session()->flash('success', "Receipt {$receipt->receipt_number} sent to {$receipt->client->email}.");
    }

    /** Stream a receipt PDF inline for viewing in the browser. */
    public function viewReceipt(string $receiptUlid, ReceiptPdfService $pdfService): StreamedResponse|BinaryFileResponse
    {
        $receipt = $this->findReceiptOrFail($receiptUlid, $pdfService);

        return Storage::response('public/'.$receipt->pdf_path, $receipt->receipt_number.'.pdf');
    }

    /** Force-download a receipt PDF. */
    public function downloadReceipt(string $receiptUlid, ReceiptPdfService $pdfService): StreamedResponse|BinaryFileResponse
    {
        $receipt = $this->findReceiptOrFail($receiptUlid, $pdfService);

        return Storage::download('public/'.$receipt->pdf_path, $receipt->receipt_number.'.pdf');
    }

    /** Look up a receipt, regenerating its PDF if it's missing. */
    private function findReceiptOrFail(string $receiptUlid, ReceiptPdfService $pdfService): Receipt
    {
        $receipt = Receipt::where('ulid', $receiptUlid)->firstOrFail();

        if (! $receipt->pdf_path || ! Storage::exists('public/'.$receipt->pdf_path)) {
            $pdfService->generate($receipt);
        }

        return $receipt;
    }

    public function render(): View
    {
        return view('livewire.receipts.receipt-index');
    }
}
