<?php

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Services\NotificationService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class InvoiceIndex extends Component
{
    use WithPagination, \App\Traits\WithSorting;

    public string $search = '';
    public string $statusFilter = '';
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    #[Computed]
    public function invoices()
    {
        $query = Invoice::with(['client', 'project'])
            ->whereHas('client') // Ensure client is not soft-deleted
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('client', function ($sq) {
                            $sq->where('name', 'like', '%' . $this->search . '%')
                              ->orWhere('company_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            });

        return $this->applySorting($query)->paginate(15);
    }

    public function downloadPdf(int $invoiceId, InvoicePdfService $pdfService)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        if (!$invoice->pdf_path || !Storage::exists('public/' . $invoice->pdf_path)) {
            $pdfService->generate($invoice);
        }

        return Storage::download('public/' . $invoice->pdf_path);
    }

    public function sendInvoice(int $invoiceId, NotificationService $notificationService)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $notificationService->sendInvoice($invoice);
        
        session()->flash('success', "Invoice {$invoice->invoice_number} sent to {$invoice->client->email}.");
    }

    public function markAsPaid(int $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoice->update(['status' => 'Paid']);
        
        session()->flash('success', "Invoice {$invoice->invoice_number} marked as Paid.");
    }

    public function render()
    {
        return view('livewire.invoices.invoice-index');
    }
}
