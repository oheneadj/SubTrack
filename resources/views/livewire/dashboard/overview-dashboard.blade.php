<div>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="flex items-center gap-2 mt-3 sm:mt-0">
            <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm flex items-center gap-2 whitespace-nowrap" wire:navigate>
                <x-icon-plus class="w-4 h-4" />
                <span>New Invoice</span>
            </a>
        </div>
    </div>

    {{-- Alert Banner (only when critical > 0) --}}
    @if($this->stats['critical'] > 0)
        <x-ui.alert-banner
            :message="$this->stats['critical'] . ' subscription(s) expiring within 7 days — immediate action required.'"
            :count="$this->stats['critical']"
            actionLabel="View renewals"
            :actionLink="route('renewals.index')"
        />
    @endif

    {{-- Stats Row (6 columns) --}}
    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-6 gap-4 mb-8">
        <x-ui.stat-card
            label="Critical"
            :value="$this->stats['critical']"
            icon="alert-triangle"
            variant="critical"
            :href="route('renewals.index')"
        />
        <x-ui.stat-card
            label="Expiring"
            :value="$this->stats['warning']"
            icon="clock"
            variant="warning"
            :href="route('renewals.index')"
        />
        <x-ui.stat-card
            label="Healthy"
            :value="$this->stats['healthy']"
            icon="check"
            variant="healthy"
            :href="route('subscriptions.index')"
        />
        <x-ui.stat-card
            label="Awaiting"
            :value="$this->stats['awaiting']"
            icon="currency-dollar"
            variant="info"
            :href="route('invoices.index')"
        />
        <x-ui.stat-card
            label="Overdue"
            :value="$this->stats['overdue']"
            icon="alert-circle"
            variant="critical"
            :href="route('invoices.index')"
        />
        <x-ui.stat-card
            label="Clients"
            :value="$this->stats['total_clients']"
            icon="users"
            variant="neutral"
            :href="route('clients.index')"
        />
    </div>

    {{-- Finance Summary Section --}}
    <h2 class="text-lg font-bold text-slate-800 tracking-tight mb-4">Finance Summary</h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Revenue Chart Card --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between" x-data="comparisonChart({{ json_encode($comparisonData) }})">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800">Revenue vs. Expenses</h3>
                    <p class="text-xs text-slate-500">Performance over the last 12 months</p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <div class="flex items-center gap-3 text-[10px] font-bold">
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <span class="text-slate-500 uppercase">Revenue</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                            <span class="text-slate-500 uppercase">Expenses</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h-[180px] w-full relative">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Mini Stats Column --}}
        <div class="grid grid-cols-1 row-span-2 gap-4">
            <x-ui.stat-card
                label="Total Revenue"
                value="${{ number_format($this->financeStats['total_revenue'], 2) }}"
                icon="currency-dollar"
                variant="healthy"
                :href="route('finances.index')"
            />
            <x-ui.stat-card
                label="Outstanding"
                value="${{ number_format($this->financeStats['outstanding'], 2) }}"
                icon="file-invoice"
                variant="warning"
                :href="route('finances.index')"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <x-ui.stat-card
            label="Est. MRR"
            value="${{ number_format($this->financeStats['mrr'], 2) }}"
            icon="calculator"
            variant="info"
            :href="route('finances.index')"
        />
        <x-ui.stat-card
            label="Annual Costs"
            value="${{ number_format($this->financeStats['costs'], 2) }}"
            icon="credit-card"
            variant="critical"
            :href="route('finances.index')"
        />
    </div>

    {{-- Two Column Section: Critical + Warning Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Critical Expirations --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold flex items-center gap-2 text-slate-800">
                    <x-icon-alert-triangle class="w-4 h-4 text-error" />
                    Critical Expirations
                </h3>
                <a href="{{ route('renewals.index') }}" class="text-xs text-primary font-semibold hover:underline" wire:navigate>View all</a>
            </div>

            @if($this->criticalSubscriptions->isEmpty())
                <x-ui.empty-state icon="check" title="All clear" message="No subscriptions expiring in the next 7 days." />
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="bg-slate-50/50 text-xs">Client / Project</th>
                                <th class="bg-slate-50/50 text-xs">Service</th>
                                <th class="bg-slate-50/50 text-xs">Days</th>
                                <th class="bg-slate-50/50 text-xs"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->criticalSubscriptions as $sub)
                                <tr class="hover:bg-slate-50/50">
                                    <td>
                                        <div class="font-bold text-sm text-slate-800">{{ $sub->project?->client?->name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate max-w-[140px]">{{ $sub->project?->project_name }}</div>
                                    </td>
                                    <td>
                                        <x-ui.badge-status :status="$sub->service_type->value" />
                                    </td>
                                    <td>
                                        <x-ui.days-pill :days="$sub->days_until_expiry" />
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            wire:click="sendReminder({{ $sub->id }})"
                                            wire:loading.attr="disabled"
                                            class="btn btn-primary btn-xs text-white"
                                            title="Send Reminder"
                                        >
                                            <span wire:loading.remove wire:target="sendReminder({{ $sub->id }})">
                                                <x-icon-send class="w-3.5 h-3.5" />
                                            </span>
                                            <span wire:loading wire:target="sendReminder({{ $sub->id }})">
                                                <span class="loading loading-spinner loading-xs"></span>
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Expiring This Month --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold flex items-center gap-2 text-slate-800">
                    <x-icon-clock class="w-4 h-4 text-warning" />
                    Expiring This Month
                </h3>
                <a href="{{ route('renewals.index') }}" class="text-xs text-primary font-semibold hover:underline" wire:navigate>View all</a>
            </div>

            @if($this->warningSubscriptions->isEmpty())
                <x-ui.empty-state icon="check" title="All clear" message="No subscriptions expiring in the next 30 days." />
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="bg-slate-50/50 text-xs">Client / Project</th>
                                <th class="bg-slate-50/50 text-xs">Service</th>
                                <th class="bg-slate-50/50 text-xs">Days</th>
                                <th class="bg-slate-50/50 text-xs"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->warningSubscriptions as $sub)
                                <tr class="hover:bg-slate-50/50">
                                    <td>
                                        <div class="font-bold text-sm text-slate-800">{{ $sub->project?->client?->name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate max-w-[140px]">{{ $sub->project?->project_name }}</div>
                                    </td>
                                    <td>
                                        <x-ui.badge-status :status="$sub->service_type->value" />
                                    </td>
                                    <td>
                                        <x-ui.days-pill :days="$sub->days_until_expiry" />
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            wire:click="sendReminder({{ $sub->id }})"
                                            wire:loading.attr="disabled"
                                            class="btn btn-primary btn-xs text-white"
                                            title="Send Reminder"
                                        >
                                            <span wire:loading.remove wire:target="sendReminder({{ $sub->id }})">
                                                <x-icon-send class="w-3.5 h-3.5" />
                                            </span>
                                            <span wire:loading wire:target="sendReminder({{ $sub->id }})">
                                                <span class="loading loading-spinner loading-xs"></span>
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    {{-- Three Column Section: Invoices + Revenue + Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Invoices (wider) --}}
        <section class="lg:col-span-1 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm">Recent Invoices</h3>
                <a href="{{ route('invoices.index') }}" class="text-xs text-primary font-semibold hover:underline" wire:navigate>View all</a>
            </div>

            @if($this->recentInvoices->isEmpty())
                <x-ui.empty-state icon="file-invoice" title="No invoices" message="Create your first invoice to see it here." />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($this->recentInvoices as $invoice)
                        <a href="{{ route('invoices.edit', $invoice) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors" wire:navigate>
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ $invoice->invoice_number }}</p>
                                <p class="text-[11px] text-slate-500">{{ $invoice->client?->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-slate-800">${{ number_format($invoice->total_amount, 2) }}</p>
                                <x-ui.badge-invoice-status :status="$invoice->status" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Recent Activity Feed --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-sm">Recent Activity</h3>
            </div>

            @if($this->activityFeed->isEmpty())
                <x-ui.empty-state icon="list-details" title="No activity" message="Activity will appear here as you use the app." />
            @else
                <div class="px-5 py-2">
                    @foreach($this->activityFeed as $log)
                        <x-ui.activity-item
                            :type="$log->event_type"
                            :description="$log->description"
                            :time="$log->created_at->diffForHumans()"
                        />
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition>
            <div class="alert alert-success shadow-lg rounded-xl">
                <x-icon-check class="w-5 h-5" />
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
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
                            pointRadius: 2,
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
                                font: { size: 9 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 6
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 9 },
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
