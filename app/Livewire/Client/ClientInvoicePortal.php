<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Client portal — shows all invoices for the authenticated client.
 * Clients log in via magic link (no password required).
 */
#[Layout('components.layouts.public')]
class ClientInvoicePortal extends Component
{
    public Client $client;

    /** @var string Filter by invoice status, empty string = all */
    public string $statusFilter = '';

    public function mount(): void
    {
        $clientId = session('client_id');
        $this->client = Client::findOrFail($clientId);
    }

    /** @return Collection<int, Invoice> */
    #[Computed]
    public function invoices(): Collection
    {
        return Invoice::where('client_id', $this->client->id)
            ->with(['items', 'payments'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('issued_date')
            ->get();
    }

    /** Generate a signed payment URL for a given invoice ULID. */
    public function paymentUrl(string $invoiceUlid): string
    {
        return URL::signedRoute('invoice.pay', ['invoice' => $invoiceUlid]);
    }

    public function render(): View
    {
        return view('livewire.client.client-invoice-portal');
    }
}
