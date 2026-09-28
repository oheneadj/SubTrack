<div>
    <x-ui.page-header title="Invoices" subtitle="Manage client billing and payment status">
        <x-ui.button as="a" href="{{ route('invoices.create') }}">
            <x-icon-plus class="w-4 h-4" />
            <span>Create Invoice</span>
        </x-ui.button>
    </x-ui.page-header>


    <x-ui.toolbar searchModel="search" searchPlaceholder="Search invoice # or client...">
        <select wire:model.live="statusFilter" class="select select-bordered shrink-0 w-full md:w-48">
            <option value="">All Statuses</option>
            <option value="Draft">Draft</option>
            <option value="Sent">Sent</option>
            <option value="Partially Paid">Partially Paid</option>
            <option value="Paid">Paid</option>
            <option value="Overdue">Overdue</option>
        </select>

        <x-ui.button variant="secondary" soft wire:click="export" class="shrink-0 whitespace-nowrap">
            <x-icon-file-invoice class="w-4 h-4" />
            <span>Export CSV</span>
        </x-ui.button>
    </x-ui.toolbar>

    @if($this->invoices->isEmpty())
        <x-ui.empty-state 
            icon="file-invoice" 
            title="No invoices yet" 
            message="Create your first invoice to start billing clients."
        />
    @else
        <x-ui.data-table :headers="['invoice_number' => 'Invoice #', 'Client', 'issued_date' => 'Date / Due', 'total_usd' => 'Total', 'status' => 'Status', 'Actions']" :sortColumn="$sortColumn" :sortDirection="$sortDirection">
            @foreach($this->invoices as $invoice)
                <tr>
                    <td class="font-bold text-blue-600">
                        <a href="{{ route('invoices.edit', $invoice) }}">{{ $invoice->invoice_number }}</a>
                    </td>
                    <td>
                        @if($invoice->client)
                            <div class="font-bold">{{ $invoice->client->name }}</div>
                            <div class="text-xs text-slate-500">{{ $invoice->client->company_name }}</div>
                        @else
                            <div class="font-bold text-slate-400 italic">Unknown Client</div>
                        @endif
                    </td>
                    <td>
                        <div class="text-sm font-medium">{{ $invoice->issued_date->format('M d, Y') }}</div>
                        <div class="text-[10px] uppercase {{ ! $invoice->isPaid() && $invoice->due_date->isPast() ? 'text-red-500 font-bold' : 'text-slate-400' }}">
                            Due: {{ $invoice->due_date->format('M d, Y') }}
                        </div>
                    </td>
                    <td class="font-bold">
                        {{ $invoice->formatted_total_amount }}
                        @if($invoice->status === App\Enums\InvoiceStatus::PartiallyPaid)
                            <div class="text-[10px] font-medium text-amber-600">{{ $invoice->formatted_balance_due }} due</div>
                        @endif
                    </td>
                        <td>
                            <x-ui.badge-invoice-status :status="$invoice->status" />
                        </td>
                    <td class="text-right">
                        <x-ui.action-menu
                            editAction="window.location.href='{{ route('invoices.edit', $invoice) }}'"
                            :slotCount="($invoice->isPaid() ? 2 : 3) + ($invoice->amount_paid > 0 ? 1 : 0)"
                        >
                            <x-ui.button wire:click="downloadPdf('{{ $invoice->ulid }}')" wire:loading.attr="disabled" wire:target="downloadPdf('{{ $invoice->ulid }}')" title="Download PDF" size="xs">
                                <x-icon-photo class="w-3.5 h-3.5" wire:loading.remove wire:target="downloadPdf('{{ $invoice->ulid }}')" />
                                <span class="loading loading-spinner loading-xs" wire:loading wire:target="downloadPdf('{{ $invoice->ulid }}')"></span>
                                Download
                            </x-ui.button>
                            <x-ui.button wire:click="sendInvoice('{{ $invoice->ulid }}')" wire:loading.attr="disabled" wire:target="sendInvoice('{{ $invoice->ulid }}')" title="Send to Client" variant="info" size="xs">
                                <x-icon-mail class="w-3.5 h-3.5" wire:loading.remove wire:target="sendInvoice('{{ $invoice->ulid }}')" />
                                <span class="loading loading-spinner loading-xs" wire:loading wire:target="sendInvoice('{{ $invoice->ulid }}')"></span>
                                Send
                            </x-ui.button>
                            @if(! $invoice->isPaid())
                                <x-ui.button wire:click="openRecordPayment('{{ $invoice->ulid }}')" title="Record Payment" variant="success" size="xs">
                                    <x-icon-circle-check class="w-3.5 h-3.5" />
                                    Record Payment
                                </x-ui.button>
                            @endif
                            @if($invoice->amount_paid > 0)
                                <x-ui.button as="a" href="{{ route('receipts.index', ['invoice' => $invoice->ulid]) }}" title="View Receipts" variant="secondary" size="xs">
                                    <x-icon-file-invoice class="w-3.5 h-3.5" />
                                    Receipts
                                </x-ui.button>
                            @endif
                        </x-ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            {{ $this->invoices->links() }}
        </div>
    @endif

    <x-ui.modal id="record-payment-modal" maxWidth="sm">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Record Payment</h3>

        <div class="flex flex-col gap-1 w-full mb-4">
            <label class="text-sm font-semibold text-slate-700">Amount received ($)</label>
            <input type="number" step="0.01" min="0.01" wire:model="recordPaymentAmount"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            @error('recordPaymentAmount')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancel</x-ui.button>
            <x-ui.button type="button" variant="success" wire:click="submitRecordPayment" wire:loading.attr="disabled" wire:target="submitRecordPayment">
                <span class="loading loading-spinner loading-xs" wire:loading wire:target="submitRecordPayment"></span>
                Record Payment
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
