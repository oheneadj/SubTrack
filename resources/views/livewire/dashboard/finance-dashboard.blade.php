<div>
    <x-ui.page-header title="Finance Dashboard" subtitle="High-level overview of revenue, costs, and cash flow" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
        <x-ui.stat-card
            label="Total Revenue"
            :value="\App\Support\Money::compact($totalRevenue)"
            icon="currency-dollar"
            variant="healthy"
        />
        <x-ui.stat-card
            label="Outstanding"
            :value="\App\Support\Money::compact($outstandingRevenue)"
            icon="file-invoice"
            variant="warning"
        />
        <x-ui.stat-card
            label="Unbilled"
            :value="\App\Support\Money::compact($draftInvoiceTotal)"
            icon="file-invoice"
            variant="neutral"
        />
        <x-ui.stat-card
            label="Monthly Revenue"
            :value="\App\Support\Money::compact($mrr)"
            icon="calculator"
            variant="info"
        />
        <x-ui.stat-card
            label="Provider Costs"
            :value="\App\Support\Money::compact($totalCosts)"
            icon="credit-card"
            variant="critical"
        />
        {{-- Realized profit from paid renewals only — was already computed
             every page load and silently thrown away before this card existed. --}}
        <x-ui.stat-card
            label="Profit"
            :value="\App\Support\Money::compact($profit)"
            icon="trending-up"
            :variant="$profit >= 0 ? 'healthy' : 'critical'"
        />
    </div>

    {{-- Churn signal — no dashboard previously showed a subscription
         lapsing without renewal at all; it just silently disappeared from
         "Active". Only shown when something has actually churned recently. --}}
    @if($churn['count'] > 0)
        <x-ui.alert-banner
            :message="$churn['count'] . ' subscription(s) auto-cancelled in the last 90 days (grace period lapsed, no renewal) — an estimated $' . number_format($churn['lostMonthlyRevenue'], 2) . '/mo in recurring revenue lost.'"
            :count="$churn['count']"
            actionLabel="View subscriptions"
            :actionLink="route('subscriptions.index')"
        />
    @endif

    {{-- Comparison Chart Section --}}
    <x-ui.card x-data="comparisonChart({{ json_encode($comparisonData) }})" class="mb-8">
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
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Recent Revenue --}}
        <x-ui.card :padding="false">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <x-icon-circle-check class="w-5 h-5 text-green-500" />
                    Recent Payments Received
                </h3>
            </div>
            <div class="p-0">
                @if($recentPayments->isEmpty())
                    <div class="p-8 text-center text-slate-500 text-sm">No recent payments.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentPayments as $payment)
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                                        <x-icon-currency-dollar class="w-5 h-5 text-green-600" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $payment->client_name }}</div>
                                        <div class="text-xs text-slate-500">
                                            @if($payment->route)
                                                <a href="{{ $payment->route }}" class="hover:underline hover:text-blue-600" wire:navigate>{{ $payment->reference }}</a>
                                            @else
                                                {{ $payment->reference }}
                                            @endif
                                            &middot; {{ $payment->type === 'invoice' ? 'Invoice' : 'Renewal' }}
                                            &middot; {{ $payment->date->format('M d, Y') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="font-bold text-green-600 text-right">
                                    +${{ number_format($payment->amount, 2) }}
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
        </x-ui.card>

        {{-- Upcoming Renewals: cost and billed revenue side by side, so the
             forecast shows both halves of the same upcoming renewal --}}
        <x-ui.card :padding="false">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <x-icon-calendar-due class="w-5 h-5 text-orange-500" />
                    Upcoming Renewals
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
                                <div class="text-right">
                                    <div class="font-bold text-slate-700">
                                        {{ $sub->formatted_client_renewal_cost_usd }}
                                        <div class="text-[10px] font-normal text-slate-400 uppercase">Bill</div>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-1">
                                        {{ $sub->formatted_renewal_cost_usd }} cost
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                <a href="{{ route('renewals.index') }}" class="text-sm font-medium text-blue-600 hover:underline" wire:navigate>View Renewal Tracker &rarr;</a>
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        {{-- Cost by Provider — "Provider Costs" above is a single
             lump figure; this breaks it down by which provider is actually
             responsible for the spend. --}}
        <x-ui.card :padding="false">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <x-icon-credit-card class="w-5 h-5 text-slate-500" />
                    Provider Costs Breakdown
                </h3>
            </div>
            <div class="p-0">
                @if(empty($costByProvider))
                    <div class="p-8 text-center text-slate-500 text-sm">No provider costs recorded yet.</div>
                @else
                    @php $maxProviderAmount = collect($costByProvider)->max('amount') ?: 1; @endphp
                    <div class="divide-y divide-slate-100">
                        @foreach($costByProvider as $entry)
                            <div class="p-4" wire:key="provider-cost-{{ $entry['name'] }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-slate-800 text-sm">{{ $entry['name'] }}</span>
                                    <span class="font-bold text-slate-700 text-sm">${{ number_format($entry['amount'], 2) }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="bg-slate-500 h-2 rounded-full" style="width: {{ max(4, (int) round($entry['amount'] / $maxProviderAmount * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-ui.card>

        {{-- Top Clients by Revenue — no dashboard previously showed which
             clients the business actually depends on. --}}
        <x-ui.card :padding="false">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <x-icon-users class="w-5 h-5 text-slate-500" />
                    Top Clients by Revenue
                </h3>
            </div>
            <div class="p-0">
                @if(empty($topClients))
                    <div class="p-8 text-center text-slate-500 text-sm">No revenue recorded yet.</div>
                @else
                    @php $maxClientAmount = collect($topClients)->max('amount') ?: 1; @endphp
                    <div class="divide-y divide-slate-100">
                        @foreach($topClients as $entry)
                            <div class="p-4" wire:key="top-client-{{ $entry['name'] }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-slate-800 text-sm">{{ $entry['name'] }}</span>
                                    <span class="font-bold text-slate-700 text-sm">${{ number_format($entry['amount'], 2) }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ max(4, (int) round($entry['amount'] / $maxClientAmount * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-ui.card>
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
