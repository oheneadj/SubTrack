<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Livewire\Concerns\RecordsManualPayments;
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
    use RecordsManualPayments, WithPagination, WithSorting;

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

        $this->notifySuccess("Invoice {$invoice->invoice_number} sent to {$invoice->client->email}.");
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
