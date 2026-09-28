<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\RecordManualPaymentAction;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvoiceAlreadyPaidException;
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

    /** Whether the Record Payment panel is open for the scoped invoice. */
    public bool $showRecordPayment = false;

    /** Amount entered in the Record Payment panel, in dollars. */
    public $recordPaymentAmount = 0;

    #[Computed]
    public function scopedInvoice(): ?Invoice
    {
        if (! $this->invoice) {
            return null;
        }

        return Invoice::with('client')->where('ulid', $this->invoice)->first();
    }

    /** True once a receipt already exists covering exactly what's been paid so far — nothing new to receipt. */
    #[Computed]
    public function scopedInvoiceFullyReceipted(): bool
    {
        if (! $this->scopedInvoice) {
            return false;
        }

        return $this->scopedInvoice->receipts()
            ->where('amount_usd', $this->scopedInvoice->amount_paid)
            ->exists();
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

    /** Opens the Record Payment panel, pre-filled with the invoice's full remaining balance. */
    public function openRecordPayment(): void
    {
        if (! $this->scopedInvoice) {
            return;
        }

        $this->recordPaymentAmount = round($this->scopedInvoice->balance_due / 100, 2);
        $this->showRecordPayment = true;
    }

    /** Records a manual payment against the scoped invoice, without leaving the receipts page. */
    public function submitRecordPayment(): void
    {
        if (! $this->scopedInvoice) {
            return;
        }

        $this->validate([
            'recordPaymentAmount' => 'required|numeric|min:0.01',
        ]);

        $amountCents = (int) round((float) $this->recordPaymentAmount * 100);

        try {
            (new RecordManualPaymentAction)->execute($this->scopedInvoice, $amountCents);
        } catch (InvoiceAlreadyPaidException|InvalidPaymentAmountException $e) {
            $this->addError('recordPaymentAmount', $e->getMessage());

            return;
        }

        $this->showRecordPayment = false;
        unset($this->scopedInvoice, $this->scopedInvoiceFullyReceipted, $this->receipts);

        $invoice = $this->scopedInvoice;
        session()->flash('success', $invoice->isPaid()
            ? "Invoice {$invoice->invoice_number} marked as Paid."
            : "Payment recorded. Invoice {$invoice->invoice_number} is now Partially Paid — {$invoice->formatted_balance_due} remaining.");
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

    /**
     * Force-download a receipt PDF. Viewing it inline instead is a plain
     * link to ReceiptPdfController@view — a real browser navigation, not a
     * Livewire action — since Livewire's file-download mechanism always
     * forces a save-as regardless of the response's Content-Disposition
     * header, making a Livewire-driven "view" indistinguishable from download.
     */
    public function downloadReceipt(string $receiptUlid, ReceiptPdfService $pdfService): StreamedResponse|BinaryFileResponse
    {
        $receipt = Receipt::where('ulid', $receiptUlid)->firstOrFail();

        if (! $receipt->pdf_path || ! Storage::exists('public/'.$receipt->pdf_path)) {
            $pdfService->generate($receipt);
        }

        return Storage::download('public/'.$receipt->pdf_path, $receipt->receipt_number.'.pdf');
    }

    public function render(): View
    {
        return view('livewire.receipts.receipt-index');
    }
}
