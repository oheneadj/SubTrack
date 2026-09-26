<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Payment\GatewayRegistry;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Public invoice payment page — accessible via signed URL, no auth required.
 * Dynamically renders all registered payment gateways as pay buttons.
 */
#[Layout('components.layouts.public')]
class PublicInvoicePage extends Component
{
    public Invoice $invoice;

    /** @var array<string, string> gateway slug => label */
    public array $gateways = [];

    public function mount(string $invoice, GatewayRegistry $registry): void
    {
        $this->invoice = Invoice::where('ulid', $invoice)
            ->with(['client', 'items', 'payments'])
            ->firstOrFail();

        foreach ($registry->all() as $slug => $gateway) {
            $this->gateways[$slug] = $gateway->label();
        }
    }

    public function render(): View
    {
        return view('livewire.public.public-invoice-page', [
            'invoice' => $this->invoice,
            'gateways' => $this->gateways,
            'isPaid' => $this->invoice->status === InvoiceStatus::Paid,
        ]);
    }
}
