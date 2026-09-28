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

            @if($this->invoicePayments->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">Payments</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-slate-400 uppercase">
                                    <th class="pb-2 font-semibold">Date</th>
                                    <th class="pb-2 font-semibold">Method</th>
                                    <th class="pb-2 font-semibold">Amount</th>
                                    <th class="pb-2 font-semibold">Status</th>
                                    <th class="pb-2 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->invoicePayments as $payment)
                                    <tr wire:key="payment-{{ $payment->id }}" class="border-t border-slate-100">
                                        <td class="py-2 text-slate-600">{{ ($payment->paid_at ?? $payment->created_at)->format('M d, Y') }}</td>
                                        <td class="py-2 text-slate-600 capitalize">{{ $payment->gateway }}</td>
                                        <td class="py-2 font-semibold {{ $payment->status->value === 'voided' ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $payment->formatted_amount }}</td>
                                        <td class="py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $payment->status->color() }}">{{ $payment->status->label() }}</span>
                                            @if($payment->void_reason)
                                                <div class="text-xs text-slate-400 mt-0.5">{{ $payment->void_reason }}</div>
                                            @endif
                                        </td>
                                        <td class="py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($payment->status->value === 'succeeded')
                                                    @if($payment->hasReceipt())
                                                        <span class="text-xs font-semibold text-green-600 flex items-center gap-1 shrink-0">
                                                            <x-icon-check class="w-3.5 h-3.5" /> Receipted
                                                        </span>
                                                    @else
                                                        <x-ui.button wire:click="generateReceipt('{{ $payment->ulid }}')" wire:loading.attr="disabled" wire:target="generateReceipt('{{ $payment->ulid }}')" title="Generate Receipt" variant="success" size="xs">
                                                            <x-icon-file-invoice class="w-3.5 h-3.5" wire:loading.remove wire:target="generateReceipt('{{ $payment->ulid }}')" />
                                                            <span class="loading loading-spinner loading-xs" wire:loading wire:target="generateReceipt('{{ $payment->ulid }}')"></span>
                                                            Receipt
                                                        </x-ui.button>
                                                    @endif
                                                @endif
                                                @if($payment->isEditable())
                                                    <x-ui.button wire:click="openEditPayment('{{ $payment->ulid }}')" title="Edit" size="xs">
                                                        <x-icon-edit class="w-3.5 h-3.5" />
                                                        Edit
                                                    </x-ui.button>
                                                @endif
                                                @if($payment->isVoidable())
                                                    <x-ui.button wire:click="openVoidPayment('{{ $payment->ulid }}')" title="Void" variant="error" size="xs">
                                                        <x-icon-x class="w-3.5 h-3.5" />
                                                        Void
                                                    </x-ui.button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </x-ui.card>
    @endif

    <x-ui.toolbar searchModel="search" searchPlaceholder="Search receipt # or client..." />

    @if($this->receipts->isEmpty())
        <x-ui.empty-state
            icon="file-invoice"
            title="No receipts yet"
            :message="$this->scopedInvoice ? 'Generate one from a payment in the table above once one has been received.' : 'Receipts generated for invoices and subscription renewals will show up here.'"
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
                    <td class="font-bold {{ $receipt->isInvalidated() ? 'text-slate-400 line-through' : '' }}">
                        {{ $receipt->formatted_amount_usd }}
                        @if($receipt->isInvalidated())
                            <div class="text-[10px] font-medium text-red-500 no-underline" title="{{ $receipt->invalidated_reason }}">
                                <x-icon-alert-circle class="w-3 h-3 inline" /> Invalidated
                            </div>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-ui.action-menu :slotCount="$receipt->isInvalidated() ? 2 : 3">
                            <x-ui.button as="a" href="{{ route('receipts.view', $receipt) }}" target="_blank" title="View" size="xs">
                                <x-icon-photo class="w-3.5 h-3.5" />
                                View
                            </x-ui.button>
                            <x-ui.button wire:click="downloadReceipt('{{ $receipt->ulid }}')" wire:loading.attr="disabled" wire:target="downloadReceipt('{{ $receipt->ulid }}')" title="Download" size="xs">
                                <x-icon-photo class="w-3.5 h-3.5" wire:loading.remove wire:target="downloadReceipt('{{ $receipt->ulid }}')" />
                                <span class="loading loading-spinner loading-xs" wire:loading wire:target="downloadReceipt('{{ $receipt->ulid }}')"></span>
                                Download
                            </x-ui.button>
                            @if(! $receipt->isInvalidated())
                                <x-ui.button wire:click="sendReceipt('{{ $receipt->ulid }}')" wire:loading.attr="disabled" wire:target="sendReceipt('{{ $receipt->ulid }}')" title="Send to Client" variant="info" size="xs">
                                    <x-icon-mail class="w-3.5 h-3.5" wire:loading.remove wire:target="sendReceipt('{{ $receipt->ulid }}')" />
                                    <span class="loading loading-spinner loading-xs" wire:loading wire:target="sendReceipt('{{ $receipt->ulid }}')"></span>
                                    Send
                                </x-ui.button>
                            @endif
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

    <x-ui.modal id="edit-payment-modal" maxWidth="sm">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Edit Payment</h3>

        <div class="flex flex-col gap-1 w-full mb-4">
            <label class="text-sm font-semibold text-slate-700">Corrected amount ($)</label>
            <input type="number" step="0.01" min="0.01" wire:model="editPaymentAmount"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            @error('editPaymentAmount')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-1 w-full mb-4">
            <label class="text-sm font-semibold text-slate-700">Date received</label>
            <input type="date" wire:model="editPaymentDate" max="{{ now()->format('Y-m-d') }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            @error('editPaymentDate')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancel</x-ui.button>
            <x-ui.button type="button" variant="success" wire:click="submitEditPayment" wire:loading.attr="disabled" wire:target="submitEditPayment">
                <span class="loading loading-spinner loading-xs" wire:loading wire:target="submitEditPayment"></span>
                Save Correction
            </x-ui.button>
        </div>
    </x-ui.modal>

    <x-ui.modal id="void-payment-modal" maxWidth="sm">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Void Payment</h3>
        <p class="text-sm text-slate-500 mb-4">The original record is kept for audit — this only excludes it from the invoice's amount paid.</p>

        <div class="flex flex-col gap-1 w-full mb-4">
            <label class="text-sm font-semibold text-slate-700">Reason {{ $this->voidReasonRequired ? '' : '(optional)' }}</label>
            <input type="text" wire:model="voidReason" placeholder="e.g. Entered wrong amount"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            @error('voidReason')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancel</x-ui.button>
            <x-ui.button type="button" variant="error" wire:click="submitVoidPayment" wire:loading.attr="disabled" wire:target="submitVoidPayment">
                <span class="loading loading-spinner loading-xs" wire:loading wire:target="submitVoidPayment"></span>
                Void Payment
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
