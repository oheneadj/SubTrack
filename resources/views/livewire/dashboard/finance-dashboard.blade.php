<div>
    <x-ui.page-header title="Finance Dashboard" subtitle="High-level overview of revenue, costs, and cash flow" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-ui.stat-card 
            label="Total Revenue (Paid)" 
            value="${{ number_format($totalRevenue, 2) }}" 
            icon="currency-dollar" 
            variant="healthy" 
        />
        <x-ui.stat-card 
            label="Outstanding Revenue" 
            value="${{ number_format($outstandingRevenue, 2) }}" 
            icon="file-invoice" 
            variant="warning" 
        />
        <x-ui.stat-card 
            label="Est. Monthly MRR" 
            value="${{ number_format($mrr, 2) }}" 
            icon="calculator" 
            variant="info" 
        />
        <x-ui.stat-card 
            label="Annual Provider Costs" 
            value="${{ number_format($totalCosts, 2) }}" 
            icon="credit-card" 
            variant="critical" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Recent Revenue --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <x-icon-circle-check class="w-5 h-5 text-green-500" />
                    Recent Payments Received
                </h3>
            </div>
            <div class="p-0">
                @if($recentInvoices->isEmpty())
                    <div class="p-8 text-center text-slate-500 text-sm">No recent payments.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentInvoices as $invoice)
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                                        <x-icon-currency-dollar class="w-5 h-5 text-green-600" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $invoice->client?->name ?? 'Unknown Client' }}</div>
                                        <div class="text-xs text-slate-500">
                                            <a href="{{ route('invoices.edit', $invoice) }}" class="hover:underline hover:text-blue-600" wire:navigate>{{ $invoice->invoice_number }}</a> 
                                            &middot; {{ $invoice->updated_at->format('M d, Y') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="font-bold text-green-600 text-right">
                                    +${{ number_format($invoice->total_amount, 2) }}
                                    <div class="text-[10px] font-normal text-slate-400 uppercase">Paid</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-blue-600 hover:underline" wire:navigate>View All Invoices &rarr;</a>
            </div>
        </div>

        {{-- Upcoming Costs --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <x-icon-calendar-due class="w-5 h-5 text-orange-500" />
                    Upcoming Expenses (Renewals)
                </h3>
            </div>
            <div class="p-0">
                @if($upcomingRenewals->isEmpty())
                    <div class="p-8 text-center text-slate-500 text-sm">No upcoming renewals.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($upcomingRenewals as $sub)
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center">
                                        <x-icon-refresh class="w-5 h-5 text-orange-600" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $sub->domain_name ?: $sub->service_type->label() }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $sub->provider?->name ?? 'Unknown' }} &middot; 
                                            <span class="{{ $sub->days_until_expiry <= 30 ? 'text-orange-500 font-bold' : '' }}">
                                                Expires {{ $sub->expiry_date->format('M d, Y') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="font-bold text-slate-700 text-right">
                                    ${{ number_format($sub->renewal_cost_usd, 2) }}
                                    <div class="text-[10px] font-normal text-slate-400 uppercase">Cost</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                <a href="{{ route('renewals.index') }}" class="text-sm font-medium text-blue-600 hover:underline" wire:navigate>View Renewal Tracker &rarr;</a>
            </div>
        </div>
    </div>
</div>
