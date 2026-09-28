<div>
    <x-ui.page-header
        title="Receipts"
        :subtitle="$this->scopedInvoice ? 'Receipts for Invoice '.$this->scopedInvoice->invoice_number : 'Every receipt issued to clients, for invoice payments and subscription renewals'"
    >
        @if($this->scopedInvoice)
            <x-ui.button as="a" href="{{ route('receipts.index') }}" variant="ghost">
                <x-icon-x class="w-4 h-4" />
                <span>Clear Filter</span>
            </x-ui.button>
        @endif
    </x-ui.page-header>

    @if($this->scopedInvoice)
        <x-ui.card class="mb-6">
            <div class="flex items-start justify-between gap-6 flex-wrap">
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Customer</div>
                    <div class="font-bold text-slate-800">{{ $this->scopedInvoice->client?->name ?? 'Unknown Client' }}</div>
                    <div class="text-sm text-slate-500">{{ $this->scopedInvoice->client?->email }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Invoice</div>
                    <a href="{{ route('invoices.edit', $this->scopedInvoice) }}" class="font-bold text-blue-600">{{ $this->scopedInvoice->invoice_number }}</a>
                    <div class="text-sm text-slate-500">Total {{ $this->scopedInvoice->formatted_total_amount }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Paid So Far</div>
                    <div class="font-bold text-green-600">{{ $this->scopedInvoice->formatted_amount_paid }}</div>
                    @if(! $this->scopedInvoice->isPaid())
                        <div class="text-sm text-slate-500">{{ $this->scopedInvoice->formatted_balance_due }} due</div>
                    @endif
                </div>
                @if(! $this->scopedInvoice->isPaid())
                    <x-ui.button wire:click="openRecordPayment('{{ $this->scopedInvoice->ulid }}')" variant="success" size="sm">
                        <x-icon-circle-check class="w-4 h-4" />
                        Record Payment
                    </x-ui.button>
                @endif
            </div>

            @if($this->scopedInvoice->amount_paid > 0)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    @if($this->scopedInvoiceFullyReceipted)
                        <p class="text-sm text-slate-500">
                            <x-icon-circle-check class="w-4 h-4 inline text-green-500" />
                            A receipt already covers everything paid so far ({{ $this->scopedInvoice->formatted_amount_paid }}). Record another payment to generate a new one.
                        </p>
                    @else
                        <div class="flex items-end gap-3 flex-wrap">
                            <div class="flex-1 min-w-[200px]">
                                <x-ui.form-input label="Notes (optional)" model="newReceiptNotes" placeholder="e.g. Paid via bank transfer" />
                            </div>
                            <x-ui.button wire:click="generateReceipt" wire:loading.attr="disabled" wire:target="generateReceipt" variant="success">
                                <x-icon-circle-check class="w-4 h-4" wire:loading.remove wire:target="generateReceipt" />
                                <span class="loading loading-spinner loading-xs" wire:loading wire:target="generateReceipt"></span>
                                Generate Receipt for {{ $this->scopedInvoice->formatted_amount_paid }}
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            @endif
        </x-ui.card>
    @endif

    <x-ui.toolbar searchModel="search" searchPlaceholder="Search receipt # or client..." />

    @if($this->receipts->isEmpty())
        <x-ui.empty-state
            icon="file-invoice"
            title="No receipts yet"
            :message="$this->scopedInvoice ? 'Generate one above once a payment has been received.' : 'Receipts generated for invoices and subscription renewals will show up here.'"
        />
    @else
        <x-ui.data-table :headers="['receipt_number' => 'Receipt #', 'Client', 'Source', 'issued_date' => 'Issued', 'amount_usd' => 'Amount', 'Actions']">
            @foreach($this->receipts as $receipt)
                <tr wire:key="receipt-{{ $receipt->id }}">
                    <td class="font-mono font-medium text-slate-700">{{ $receipt->receipt_number }}</td>
                    <td>
                        @if($receipt->client)
                            <div class="font-bold">{{ $receipt->client->name }}</div>
                            <div class="text-xs text-slate-500">{{ $receipt->client->email }}</div>
                        @else
                            <div class="font-bold text-slate-400 italic">Unknown Client</div>
                        @endif
                    </td>
                    <td>
                        @if($receipt->invoice)
                            <a href="{{ route('invoices.edit', $receipt->invoice) }}" class="text-blue-600 font-medium">{{ $receipt->source_label }}</a>
                        @elseif($receipt->subscription)
                            <a href="{{ route('subscriptions.show', $receipt->subscription) }}" class="text-blue-600 font-medium">{{ $receipt->source_label }}</a>
                        @else
                            {{ $receipt->source_label }}
                        @endif
                    </td>
                    <td class="text-sm text-slate-600">{{ $receipt->issued_date->format('M d, Y') }}</td>
                    <td class="font-bold">{{ $receipt->formatted_amount_usd }}</td>
                    <td class="text-right">
                        <x-ui.action-menu :slotCount="3">
                            <x-ui.button as="a" href="{{ route('receipts.view', $receipt) }}" target="_blank" title="View" size="xs">
                                <x-icon-photo class="w-3.5 h-3.5" />
                                View
                            </x-ui.button>
                            <x-ui.button wire:click="downloadReceipt('{{ $receipt->ulid }}')" wire:loading.attr="disabled" wire:target="downloadReceipt('{{ $receipt->ulid }}')" title="Download" size="xs">
                                <x-icon-photo class="w-3.5 h-3.5" wire:loading.remove wire:target="downloadReceipt('{{ $receipt->ulid }}')" />
                                <span class="loading loading-spinner loading-xs" wire:loading wire:target="downloadReceipt('{{ $receipt->ulid }}')"></span>
                                Download
                            </x-ui.button>
                            <x-ui.button wire:click="sendReceipt('{{ $receipt->ulid }}')" wire:loading.attr="disabled" wire:target="sendReceipt('{{ $receipt->ulid }}')" title="Send to Client" variant="info" size="xs">
                                <x-icon-mail class="w-3.5 h-3.5" wire:loading.remove wire:target="sendReceipt('{{ $receipt->ulid }}')" />
                                <span class="loading loading-spinner loading-xs" wire:loading wire:target="sendReceipt('{{ $receipt->ulid }}')"></span>
                                Send
                            </x-ui.button>
                        </x-ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            {{ $this->receipts->links() }}
        </div>
    @endif

    <x-invoices.record-payment-modal />
</div>
