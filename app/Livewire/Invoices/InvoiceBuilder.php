<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Enums\ActivityEventType;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceNumberService;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

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

    public function mount(InvoiceNumberService $numberService, ?Invoice $invoice = null): void
    {
        // Handle pre-filling from query string
        $clientIdFromQuery = request()->query('clientId');
        $projectIdFromQuery = request()->query('projectId');

        if ($invoice && $invoice->exists) {
            $this->invoice = $invoice;
            $this->isEdit = true;
            $this->fill($invoice->toArray());
            // Convert cents → dollars for display in the form
            $this->subtotal = $invoice->subtotal / 100;
            $this->tax_amount = $invoice->tax_amount / 100;
            $this->total_amount = $invoice->total_amount / 100;
            $this->items = $invoice->items->map(fn ($item) => array_merge($item->toArray(), [
                'unit_price' => $item->unit_price / 100,
                'total' => $item->total / 100,
            ]))->toArray();
            $this->issued_date = $invoice->issued_date->format('Y-m-d');
            $this->due_date = $invoice->due_date->format('Y-m-d');
        } else {
            $this->invoice_number = $numberService->generate();
            $this->issued_date = now()->format('Y-m-d');
            $this->due_date = now()->addDays(14)->format('Y-m-d');
            $this->addItem();

            if ($clientIdFromQuery) {
                $client = Client::where('ulid', $clientIdFromQuery)->first();
                $this->client_id = $client?->id;
            }
            if ($projectIdFromQuery) {
                $project = Project::where('ulid', $projectIdFromQuery)->first();
                $this->project_id = $project?->id;
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
            $itemTotal = (float) $item['quantity'] * (float) $item['unit_price'];
            $this->items[$index]['total'] = $itemTotal;
            $this->subtotal += $itemTotal;
        }

        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total_amount = $this->subtotal + $this->tax_amount;
    }

    public function save(InvoicePdfService $pdfService): RedirectResponse
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'required|exists:projects,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number,'.($this->invoice->id ?? 'NULL'),
            'issued_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issued_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Convert dollar UI values to integer cents for storage
        $subtotalCents = (int) round($this->subtotal * 100);
        $taxAmountCents = (int) round($this->tax_amount * 100);
        $totalAmountCents = (int) round($this->total_amount * 100);

        $data = [
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'invoice_number' => $this->invoice_number,
            'issued_date' => $this->issued_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'tax_rate' => $this->tax_rate,
            'subtotal' => $subtotalCents,
            'tax_amount' => $taxAmountCents,
            'total_amount' => $totalAmountCents,
        ];

        DB::transaction(function () use ($data, $totalAmountCents): void {
            if ($this->isEdit) {
                $this->invoice->update($data);
                $this->invoice->items()->delete();
            } else {
                $this->invoice = Invoice::create($data);

                DashboardActivityLog::record(
                    ActivityEventType::InvoiceCreated,
                    "Invoice {$this->invoice->invoice_number} created — $".number_format($totalAmountCents / 100, 2),
                    $this->client_id,
                    ['invoice_id' => $this->invoice->id]
                );
            }

            foreach ($this->items as $item) {
                // Convert item dollar amounts to cents
                $this->invoice->items()->create(array_merge($item, [
                    'unit_price' => (int) round((float) $item['unit_price'] * 100),
                    'total' => (int) round((float) $item['total'] * 100),
                ]));
            }
        });

        // PDF generation happens outside the transaction — failure here won't roll back the saved invoice
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
        if (! $this->client_id) {
            return collect();
        }

        return Project::where('client_id', $this->client_id)->orderBy('project_name')->get();
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-builder');
    }
}
