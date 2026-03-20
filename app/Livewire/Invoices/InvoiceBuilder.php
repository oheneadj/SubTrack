<?php

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceNumberService;
use App\Services\InvoicePdfService;
use Livewire\Component;
use Illuminate\Support\Collection;

class InvoiceBuilder extends Component
{
    public ?Invoice $invoice = null;
    public bool $isEdit = false;

    // Form data
    public ?int $client_id = null;
    public $project_id = '';
    public $invoice_number = '';
    public $issued_date = '';
    public $due_date = '';
    public $status = 'Draft';
    public $notes = '';
    public $tax_rate = 0;

    // Line items
    public array $items = [];

    // Totals
    public $subtotal = 0;
    public $tax_amount = 0;
    public $total_amount = 0;

    public function mount(InvoiceNumberService $numberService, ?Invoice $invoice = null)
    {
        // Handle pre-filling from query string
        $clientIdFromQuery = request()->query('clientId');
        $projectIdFromQuery = request()->query('projectId');
        
        if ($invoice && $invoice->exists) {
            $this->invoice = $invoice;
            $this->isEdit = true;
            $this->fill($invoice->toArray());
            $this->items = $invoice->items->map(fn($item) => $item->toArray())->toArray();
            $this->issued_date = $invoice->issued_date->format('Y-m-d');
            $this->due_date = $invoice->due_date->format('Y-m-d');
        } else {
            $this->invoice_number = $numberService->generate();
            $this->issued_date = now()->format('Y-m-d');
            $this->due_date = now()->addDays(14)->format('Y-m-d');
            $this->addItem();
            
            if ($clientIdFromQuery) {
                $this->client_id = (int) $clientIdFromQuery;
            }
            if ($projectIdFromQuery) {
                $this->project_id = (int) $projectIdFromQuery;
            }
        }
        $this->recalculate();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'total' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculate();
    }

    public function updatedItems(): void
    {
        $this->recalculate();
    }

    public function updatedTaxRate(): void
    {
        $this->recalculate();
    }

    public function recalculate(): void
    {
        $this->subtotal = 0;
        foreach ($this->items as $index => $item) {
            $itemTotal = (float)$item['quantity'] * (float)$item['unit_price'];
            $this->items[$index]['total'] = $itemTotal;
            $this->subtotal += $itemTotal;
        }

        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total_amount = $this->subtotal + $this->tax_amount;
    }

    public function save(InvoicePdfService $pdfService)
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'required|exists:projects,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . ($this->invoice->id ?? 'NULL'),
            'issued_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $data = [
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'invoice_number' => $this->invoice_number,
            'issued_date' => $this->issued_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'tax_rate' => $this->tax_rate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
        ];

        if ($this->isEdit) {
            $this->invoice->update($data);
            $this->invoice->items()->delete();
        } else {
            $this->invoice = Invoice::create($data);

            \App\Models\DashboardActivityLog::record(
                \App\Enums\ActivityEventType::InvoiceCreated,
                "Invoice {$this->invoice->invoice_number} created — \${$this->total_amount}",
                $this->client_id,
                ['invoice_id' => $this->invoice->id]
            );
        }

        foreach ($this->items as $item) {
            $this->invoice->items()->create($item);
        }

        // Auto-generate PDF on save
        $pdfService->generate($this->invoice);

        session()->flash('success', $this->isEdit ? 'Invoice updated successfully.' : 'Invoice created successfully.');
        return redirect()->route('invoices.index');
    }

    public function getClientsProperty(): Collection
    {
        return Client::orderBy('name')->get();
    }

    public function getProjectsProperty(): Collection
    {
        if (!$this->client_id) return collect();
        return Project::where('client_id', $this->client_id)->orderBy('project_name')->get();
    }

    public function render()
    {
        return view('livewire.invoices.invoice-builder');
    }
}
