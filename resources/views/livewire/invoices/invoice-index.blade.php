<div>
    <x-ui.page-header title="Invoices" subtitle="Manage client billing and payment status">
        <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm flex items-center gap-2">
            <x-icon-plus class="w-4 h-4" />
            <span>Create Invoice</span>
        </a>
    </x-ui.page-header>


    <div class="flex flex-col md:flex-row gap-4 mb-6 bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="w-full">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <x-icon-search class="w-4 h-4 text-slate-400" />
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" 
                class="input input-bordered w-full pl-10" placeholder="Search invoice # or client...">
        </div>
        
        <select wire:model.live="statusFilter" class="select select-bordered w-full md:w-48">
            <option value="">All Statuses</option>
            <option value="Draft">Draft</option>
            <option value="Sent">Sent</option>
            <option value="Paid">Paid</option>
            <option value="Overdue">Overdue</option>
        </select>

        <button wire:click="export" class="btn btn-soft btn-secondary btn-sm flex items-center gap-2">
            <x-icon-file-invoice class="w-4 h-4" />
            <span>Export CSV</span>
        </button>
    </div>

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
                            <button wire:click="downloadPdf({{ $invoice->id }})" wire:loading.attr="disabled" title="Download PDF" class="btn btn-primary btn-xs text-white">
                                <span wire:loading.remove class="flex items-center gap-1">
                                    <x-icon-photo class="w-4 h-4 text-white" />Download
                                </span>
                                <span wire:loading>
                                    <span class="loading loading-spinner loading-xs"></span>
                                </span>
                            </button>
                            <button wire:click="sendInvoice({{ $invoice->id }})" wire:loading.attr="disabled" title="Send to Client" class="btn btn-info btn-xs text-white">
                                <span wire:loading.remove class="flex items-center gap-1">
                                    <x-icon-mail class="w-4 h-4 text-white" />Send
                                </span>
                                <span wire:loading>
                                    <span class="loading loading-spinner loading-xs"></span>
                                </span>
                            </button>
                            @if($invoice->status !== 'Paid')
                                <button wire:click="markAsPaid({{ $invoice->id }})" wire:loading.attr="disabled" title="Mark as Paid" class="btn btn-success btn-xs text-white">
                                    <span wire:loading.remove class="flex items-center gap-1">
                                        <x-icon-circle-check class="w-4 h-4 text-white" />Paid
                                    </span>
                                    <span wire:loading>
                                        <span class="loading loading-spinner loading-xs"></span>
                                    </span>
                                </button>
                            @endif
                            <a href="{{ route('invoices.edit', $invoice) }}" title="Edit" class="btn btn-warning btn-xs text-white">
                                <x-icon-edit class="w-4 h-4 text-white" />Edit
                            </a>
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
