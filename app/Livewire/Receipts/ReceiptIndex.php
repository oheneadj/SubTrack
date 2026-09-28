<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Actions\EditManualPaymentAction;
use App\Actions\GenerateInvoiceReceiptAction;
use App\Actions\VoidManualPaymentAction;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvalidPaymentDateException;
use App\Exceptions\PaymentNotEditableException;
use App\Exceptions\PaymentNotVoidableException;
use App\Exceptions\VoidReasonRequiredException;
use App\Livewire\Concerns\RecordsManualPayments;
use App\Mail\ReceiptMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Setting;
use App\Services\EmailLogger;
use App\Services\ReceiptPdfService;
use Carbon\CarbonImmutable;
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
    use RecordsManualPayments, WithPagination;

    public string $search = '';

    #[Url]
    public string $invoice = '';

    /** ULID of the payment currently open in the Edit Payment modal. */
    public string $editPaymentUlid = '';

    /** Amount entered in the Edit Payment modal, in dollars. */
    public $editPaymentAmount = 0;

    /** Date entered in the Edit Payment modal. */
    public string $editPaymentDate = '';

    /** ULID of the payment currently open in the Void Payment modal. */
    public string $voidPaymentUlid = '';

    /** Reason entered in the Void Payment modal. */
    public string $voidReason = '';

    #[Computed]
    public function scopedInvoice(): ?Invoice
    {
        if (! $this->invoice) {
            return null;
        }

        return Invoice::with('client')->where('ulid', $this->invoice)->first();
    }

    /** Every payment (manual and gateway) recorded against the scoped invoice, most recent first. */
    #[Computed]
    public function invoicePayments()
    {
        if (! $this->scopedInvoice) {
            return collect();
        }

        return $this->scopedInvoice->payments()->latest()->get();
    }

    #[Computed]
    public function receipts()
    {
        return Receipt::with(['client', 'invoice', 'subscription', 'payment'])
            ->when($this->scopedInvoice, fn ($query) => $query->where('invoice_id', $this->scopedInvoice->id))
            ->when($this->search, function ($query) {
                $query->where('receipt_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'));
            })
            ->latest('issued_date')
            ->paginate(15);
    }

    /** Refreshes computed properties that depend on the invoice once a payment is recorded against it. */
    protected function afterPaymentRecorded(Invoice $invoice): void
    {
        $this->refreshInvoiceComputedState();
    }

    /** Opens the Edit Payment modal, pre-filled with the payment's current amount. */
    public function openEditPayment(string $paymentUlid): void
    {
        $payment = Payment::where('ulid', $paymentUlid)->firstOrFail();

        $this->editPaymentUlid = $paymentUlid;
        $this->editPaymentAmount = round($payment->amount / 100, 2);
        $this->editPaymentDate = $payment->paid_at?->format('Y-m-d') ?? CarbonImmutable::now()->format('Y-m-d');

        $this->dispatch('open-modal', id: 'edit-payment-modal');
    }

    /** Corrects a manually-recorded payment's amount and/or received date. */
    public function submitEditPayment(EditManualPaymentAction $action): void
    {
        $this->validate([
            'editPaymentAmount' => 'required|numeric|min:0.01',
            'editPaymentDate' => 'required|date|before_or_equal:today',
        ]);

        $payment = Payment::where('ulid', $this->editPaymentUlid)->firstOrFail();
        $amountCents = (int) round((float) $this->editPaymentAmount * 100);
        $paidAt = CarbonImmutable::parse($this->editPaymentDate);

        try {
            $action->execute($payment, $amountCents, $paidAt);
        } catch (PaymentNotEditableException|InvalidPaymentAmountException $e) {
            $this->addError('editPaymentAmount', $e->getMessage());

            return;
        } catch (InvalidPaymentDateException $e) {
            $this->addError('editPaymentDate', $e->getMessage());

            return;
        }

        $this->dispatch('close-modal', id: 'edit-payment-modal');
        $this->refreshInvoiceComputedState();

        session()->flash('success', 'Payment corrected.');
    }

    #[Computed]
    public function voidReasonRequired(): bool
    {
        return filter_var(Setting::get('require_void_reason', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** Opens the Void Payment modal. */
    public function openVoidPayment(string $paymentUlid): void
    {
        $this->voidPaymentUlid = $paymentUlid;
        $this->voidReason = '';

        $this->dispatch('open-modal', id: 'void-payment-modal');
    }

    /** Voids a manually-recorded payment made by mistake, keeping the original record for audit. */
    public function submitVoidPayment(VoidManualPaymentAction $action): void
    {
        $payment = Payment::where('ulid', $this->voidPaymentUlid)->firstOrFail();

        try {
            $action->execute($payment, $this->voidReason ?: null);
        } catch (VoidReasonRequiredException $e) {
            $this->addError('voidReason', $e->getMessage());

            return;
        } catch (PaymentNotVoidableException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->dispatch('close-modal', id: 'void-payment-modal');
        $this->refreshInvoiceComputedState();

        session()->flash('success', 'Payment voided.');
    }

    /** Busts every computed property whose value depends on the scoped invoice's payment state. */
    private function refreshInvoiceComputedState(): void
    {
        unset($this->scopedInvoice, $this->invoicePayments, $this->receipts);
    }

    /**
     * Generates a receipt for one specific payment. Each payment gets its
     * own receipt — two separate payments must never merge into a single
     * receipt for their combined total, since a receipt is proof of one
     * specific transaction.
     */
    public function generateReceipt(string $paymentUlid, GenerateInvoiceReceiptAction $action): void
    {
        $payment = Payment::where('ulid', $paymentUlid)->firstOrFail();

        try {
            $action->execute($payment);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->refreshInvoiceComputedState();

        session()->flash('success', 'Receipt generated. You can download it or send it to the client below.');
    }

    /** Emails the receipt PDF to the client it was issued for. */
    public function sendReceipt(string $receiptUlid, EmailLogger $emailLogger): void
    {
        $receipt = Receipt::with('client')->where('ulid', $receiptUlid)->firstOrFail();

        if ($receipt->isInvalidated()) {
            session()->flash('error', "Receipt {$receipt->receipt_number} can't be sent — it no longer matches a valid payment.");

            return;
        }

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
