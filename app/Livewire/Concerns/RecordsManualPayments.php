<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Actions\RecordManualPaymentAction;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;

/**
 * Shared "Record Payment" modal behavior for any Livewire component that
 * lets an admin record a manual payment against an invoice — currently
 * the Invoices list and the invoice-scoped Receipts page. Keeping this in
 * one place means both stay wired to the same validation and messaging.
 */
trait RecordsManualPayments
{
    /** ULID of the invoice currently open in the Record Payment modal. */
    public string $recordPaymentInvoiceUlid = '';

    /** Amount entered in the Record Payment modal, in dollars. */
    public $recordPaymentAmount = 0;

    /** Opens the Record Payment modal, pre-filled with the invoice's full remaining balance. */
    public function openRecordPayment(string $invoiceUlid): void
    {
        $invoice = Invoice::where('ulid', $invoiceUlid)->firstOrFail();

        $this->recordPaymentInvoiceUlid = $invoiceUlid;
        $this->recordPaymentAmount = round($invoice->balance_due / 100, 2);

        $this->dispatch('open-modal', id: 'record-payment-modal');
    }

    /** Records a manual payment (cash, bank transfer, etc.) against the invoice, full or partial. */
    public function submitRecordPayment(): void
    {
        $this->validate([
            'recordPaymentAmount' => 'required|numeric|min:0.01',
        ]);

        $invoice = Invoice::where('ulid', $this->recordPaymentInvoiceUlid)->firstOrFail();
        $amountCents = (int) round((float) $this->recordPaymentAmount * 100);

        try {
            (new RecordManualPaymentAction)->execute($invoice, $amountCents);
        } catch (InvoiceAlreadyPaidException|InvalidPaymentAmountException $e) {
            $this->addError('recordPaymentAmount', $e->getMessage());

            return;
        }

        $this->dispatch('close-modal', id: 'record-payment-modal');

        $invoice->refresh();
        session()->flash('success', $invoice->isPaid()
            ? "Invoice {$invoice->invoice_number} marked as Paid."
            : "Payment recorded. Invoice {$invoice->invoice_number} is now Partially Paid — {$invoice->formatted_balance_due} remaining.");

        $this->afterPaymentRecorded($invoice);
    }

    /** Hook for the consuming component to refresh whatever depends on the invoice (e.g. cached computed properties). */
    protected function afterPaymentRecorded(Invoice $invoice): void
    {
        //
    }
}
