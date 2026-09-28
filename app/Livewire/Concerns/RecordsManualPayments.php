<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Actions\RecordManualPaymentAction;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvalidPaymentDateException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;
use Carbon\CarbonImmutable;

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

    /** Date entered in the Record Payment modal — when the payment was actually received. */
    public string $recordPaymentDate = '';

    /** Notes entered in the Record Payment modal — optional context, e.g. "Bank transfer ref #1234". */
    public string $recordPaymentNotes = '';

    /** Opens the Record Payment modal, pre-filled with the invoice's full remaining balance and today's date. */
    public function openRecordPayment(string $invoiceUlid): void
    {
        $invoice = Invoice::where('ulid', $invoiceUlid)->firstOrFail();

        $this->recordPaymentInvoiceUlid = $invoiceUlid;
        $this->recordPaymentAmount = round($invoice->balance_due / 100, 2);
        $this->recordPaymentDate = CarbonImmutable::now()->format('Y-m-d');
        $this->recordPaymentNotes = '';

        $this->dispatch('open-modal', id: 'record-payment-modal');
    }

    /** Records a manual payment (cash, bank transfer, etc.) against the invoice, full or partial. */
    public function submitRecordPayment(): void
    {
        $this->validate([
            'recordPaymentAmount' => 'required|numeric|min:0.01',
            'recordPaymentDate' => 'required|date|before_or_equal:today',
        ], [
            'recordPaymentAmount.required' => 'Enter how much was received.',
            'recordPaymentAmount.min' => 'Enter an amount greater than $0.',
            'recordPaymentDate.required' => 'Enter the date the payment was received.',
            'recordPaymentDate.before_or_equal' => "That date hasn't happened yet — pick today or an earlier date.",
        ]);

        $invoice = Invoice::where('ulid', $this->recordPaymentInvoiceUlid)->firstOrFail();
        $amountCents = (int) round((float) $this->recordPaymentAmount * 100);
        $paidAt = CarbonImmutable::parse($this->recordPaymentDate);

        try {
            (new RecordManualPaymentAction)->execute($invoice, $amountCents, $paidAt, $this->recordPaymentNotes ?: null);
        } catch (InvoiceAlreadyPaidException|InvalidPaymentAmountException $e) {
            $this->addError('recordPaymentAmount', $e->getMessage());

            return;
        } catch (InvalidPaymentDateException $e) {
            $this->addError('recordPaymentDate', $e->getMessage());

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
