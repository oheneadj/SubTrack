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

    {{-- Comparison Chart Section --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-8 shadow-sm" x-data="comparisonChart({{ json_encode($comparisonData) }})">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Revenue vs. Expenses</h3>
                <p class="text-sm text-slate-500">Historical performance over the last 12 months</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                    <span class="text-slate-600">Revenue</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-slate-300"></span>
                    <span class="text-slate-600">Expenses (Baseline)</span>
                </div>
            </div>
        </div>
        <div class="h-[300px] w-full relative">
            <canvas x-ref="canvas"></canvas>
        </div>
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

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
function comparisonChart(data) {
    return {
        init() {
            const labels = data.map(d => d.label);
            const revenue = data.map(d => d.revenue);
            const expenses = data.map(d => d.expenses);
            const ctx = this.$refs.canvas.getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: revenue,
                            borderColor: '#2563eb', // blue-600
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#2563eb',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'Expenses',
                            data: expenses,
                            borderColor: '#94a3b8', // slate-400
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 12,
                            bodySpacing: 4,
                            callbacks: {
                                label: ctx => ' ' + ctx.dataset.label + ': $' + ctx.raw.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { 
                                font: { size: 10 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 6
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 10 },
                                callback: value => '$' + value.toLocaleString()
                            }
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush
