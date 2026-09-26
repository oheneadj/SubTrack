<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Your Invoices</h1>
            <p class="text-sm text-slate-500 mt-0.5">Welcome back, {{ $this->client->name }}</p>
        </div>
        <a href="{{ route('client.logout') }}" class="text-sm text-slate-500 hover:text-red-600 transition-colors">Sign out</a>
    </div>

    {{-- Status filter --}}
    <div class="mb-5">
        <select wire:model.live="statusFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-40">
            <option value="">All invoices</option>
            <option value="Draft">Draft</option>
            <option value="Sent">Sent</option>
            <option value="Paid">Paid</option>
            <option value="Overdue">Overdue</option>
        </select>
    </div>

    @if($this->invoices->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <x-icon-file-x class="w-10 h-10 text-slate-300 mx-auto mb-3" />
            <p class="text-slate-500 text-sm">No invoices found.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->invoices as $invoice)
                <div wire:key="invoice-{{ $invoice->id }}" class="bg-white rounded-2xl border border-slate-200 overflow-hidden" x-data="{ expanded: false }">
                    <div class="p-5 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div>
                                <p class="font-bold text-slate-800 text-sm">{{ $invoice->invoice_number }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">Issued {{ $invoice->issued_date->format('M d, Y') }} · Due {{ $invoice->due_date->format('M d, Y') }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="font-bold text-slate-800">{{ $invoice->formatted_total_amount }}</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($invoice->status->value === 'Paid') bg-green-100 text-green-700
                                    @elseif($invoice->status->value === 'Overdue') bg-red-100 text-red-700
                                    @elseif($invoice->status->value === 'Sent') bg-blue-100 text-blue-700
                                    @else bg-slate-100 text-slate-600 @endif">
                                    {{ $invoice->status->value }}
                                </span>
                            </div>

                            @if(!$invoice->isPaid())
                                <a href="{{ $this->paymentUrl($invoice->ulid) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors whitespace-nowrap">
                                    Pay Now
                                </a>
                            @endif

                            @if($invoice->payments->isNotEmpty())
                                <button @click="expanded = !expanded" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                                    <x-icon-chevron-down class="w-4 h-4 transition-transform" :class="expanded ? 'rotate-180' : ''" />
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Payment history --}}
                    @if($invoice->payments->isNotEmpty())
                        <div x-show="expanded" x-collapse class="border-t border-slate-100 bg-slate-50 px-5 py-4">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Payment History</p>
                            <div class="space-y-2">
                                @foreach($invoice->payments as $payment)
                                    <div wire:key="payment-{{ $payment->id }}" class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                @if($payment->status->value === 'succeeded') bg-green-100 text-green-700
                                                @elseif($payment->status->value === 'failed') bg-red-100 text-red-700
                                                @elseif($payment->status->value === 'refunded') bg-slate-100 text-slate-600
                                                @else bg-amber-100 text-amber-700 @endif">
                                                {{ $payment->status->label() }}
                                            </span>
                                            <span class="text-slate-600 capitalize">{{ str_replace('_', ' ', $payment->method) }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 text-slate-500">
                                            <span>{{ $payment->formatted_amount }}</span>
                                            @if($payment->paid_at)
                                                <span class="text-xs">{{ $payment->paid_at->format('M d, Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
