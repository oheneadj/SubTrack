<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Enums\ActivityEventType;
use App\Models\Client;
use App\Models\DashboardActivityLog;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Subscription;
use App\Services\InvoiceNumberService;
use App\Services\InvoicePdfService;
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
            'subscription_id' => null,
        ];
    }

    /**
     * Add a line item pre-filled from one of the selected project's actual
     * subscriptions — description, and the client's renewal cost (including
     * markup) as the unit price — instead of the admin re-typing it from
     * scratch and losing the link back to the subscription entirely.
     */
    public function addSubscriptionItem(int $subscriptionId): void
    {
        $subscription = Subscription::find($subscriptionId);
        if (! $subscription) {
            return;
        }

        $serviceName = $subscription->domain_name ?: $subscription->service_type->label();
        $unitPrice = $subscription->client_renewal_cost_usd / 100;

        $this->items[] = [
            'description' => "Renewal: {$serviceName}",
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'total' => $unitPrice,
            'subscription_id' => $subscription->id,
        ];

        $this->recalculate();
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

    /**
     * No return type declared: Livewire's redirect() helper returns its own
     * Redirector wrapper (not Illuminate\Http\RedirectResponse), and PHP
     * enforces declared return types strictly on every invocation — a
     * RedirectResponse type hint here throws a TypeError the moment this
     * method actually returns, which nothing had ever exercised until now.
     */
    public function save(InvoicePdfService $pdfService)
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

    /** The selected project's own subscriptions, offered as one-click line items. */
    public function getProjectSubscriptionsProperty(): Collection
    {
        if (! $this->project_id) {
            return collect();
        }

        return Subscription::where('project_id', $this->project_id)
            ->with('provider')
            ->orderBy('domain_name')
            ->get();
    }

    /** Subscription ids already added as line items, so the picker can show them as added. */
    public function getAddedSubscriptionIdsProperty(): array
    {
        return collect($this->items)->pluck('subscription_id')->filter()->all();
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-builder');
    }
}
