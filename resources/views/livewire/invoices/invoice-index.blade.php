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
                        <div class="text-[10px] uppercase {{ $invoice->status !== 'Paid' && $invoice->due_date->isPast() ? 'text-red-500 font-bold' : 'text-slate-400' }}">
                            Due: {{ $invoice->due_date->format('M d, Y') }}
                        </div>
                    </td>
                    <td class="font-bold">${{ number_format($invoice->total_amount, 2) }}</td>
                        <td>
                            <x-ui.badge-invoice-status :status="$invoice->status" />
                        </td>
                    <td class="text-right">
                        <div class="flex gap-1 justify-end">
                            <x-ui.button wire:click="downloadPdf({{ $invoice->id }})" wire:loading.attr="disabled" title="Download PDF" size="xs" class="text-white">
                                <span wire:loading.remove class="flex items-center gap-1">
                                    <x-icon-photo class="w-4 h-4 text-white" />Download
                                </span>
                                <span wire:loading>
                                    <span class="loading loading-spinner loading-xs"></span>
                                </span>
                            </x-ui.button>
                            <x-ui.button wire:click="sendInvoice({{ $invoice->id }})" wire:loading.attr="disabled" title="Send to Client" variant="info" size="xs" class="text-white">
                                <span wire:loading.remove class="flex items-center gap-1">
                                    <x-icon-mail class="w-4 h-4 text-white" />Send
                                </span>
                                <span wire:loading>
                                    <span class="loading loading-spinner loading-xs"></span>
                                </span>
                            </x-ui.button>
                            @if($invoice->status !== 'Paid')
                                <x-ui.button wire:click="markAsPaid({{ $invoice->id }})" wire:loading.attr="disabled" title="Mark as Paid" variant="success" size="xs" class="text-white">
                                    <span wire:loading.remove class="flex items-center gap-1">
                                        <x-icon-circle-check class="w-4 h-4 text-white" />Paid
                                    </span>
                                    <span wire:loading>
                                        <span class="loading loading-spinner loading-xs"></span>
                                    </span>
                                </x-ui.button>
                            @endif
                            <x-ui.button as="a" href="{{ route('invoices.edit', $invoice) }}" title="Edit" variant="warning" size="xs" class="text-white">
                                <x-icon-edit class="w-4 h-4 text-white" />Edit
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            {{ $this->invoices->links() }}
        </div>
    @endif
</div>
