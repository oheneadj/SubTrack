<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Actions\RecordManualPaymentAction;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Services\NotificationService;
use App\Traits\WithSorting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceIndex extends Component
{
    use WithPagination, WithSorting;

    public string $search = '';

    public string $statusFilter = '';

    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    /** ULID of the invoice currently open in the Record Payment modal. */
    public string $recordPaymentInvoiceUlid = '';

    /** Amount entered in the Record Payment modal, in dollars. */
    public $recordPaymentAmount = 0;

    #[Computed]
    public function invoices()
    {
        $query = Invoice::with(['client', 'project'])
            ->whereHas('client') // Ensure client is not soft-deleted
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('invoice_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', function ($sq) {
                            $sq->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('company_name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            });

        return $this->applySorting($query)->paginate(15);
    }

    public function downloadPdf(string $invoiceUlid, InvoicePdfService $pdfService): StreamedResponse|BinaryFileResponse
    {
        $invoice = Invoice::where('ulid', $invoiceUlid)->firstOrFail();

        if (! $invoice->pdf_path || ! Storage::exists('public/'.$invoice->pdf_path)) {
            $pdfService->generate($invoice);
        }

        return Storage::download('public/'.$invoice->pdf_path);
    }

    public function sendInvoice(string $invoiceUlid, NotificationService $notificationService): void
    {
        $invoice = Invoice::where('ulid', $invoiceUlid)->firstOrFail();
        $notificationService->sendInvoice($invoice);

        session()->flash('success', "Invoice {$invoice->invoice_number} sent to {$invoice->client->email}.");
    }

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
    }

    public function export(): StreamedResponse
    {
        $query = Invoice::with(['client', 'project'])
            ->when($this->search, function ($query) {
                $query->where('invoice_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'));
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter));

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=invoices-export-'.now()->format('Y-m-d').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Invoice #', 'Client', 'Project', 'Issued Date', 'Due Date', 'Amount', 'Status']);

            $query->chunk(100, function ($invoices) use ($file) {
                foreach ($invoices as $invoice) {
                    fputcsv($file, [
                        $invoice->id,
                        $invoice->invoice_number,
                        $invoice->client?->name ?? 'N/A',
                        $invoice->project?->project_name ?? 'N/A',
                        $invoice->issued_date->format('Y-m-d'),
                        $invoice->due_date->format('Y-m-d'),
                        number_format($invoice->total_amount / 100, 2),
                        $invoice->status->value,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-index');
    }
}
