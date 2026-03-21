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
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
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
function revenueChart(data) {
    return {
        init() {
            const labels = data.map(d => d.label);
            const values = data.map(d => d.total);
            const ctx = this.$refs.canvas.getContext('2d');
            
            // Create gradient for modern finance look
            let gradient = ctx.createLinearGradient(0, 0, 0, 140);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); // blue-500 fading out
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        borderColor: '#2563eb', // blue-600
                        backgroundColor: gradient,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4, // Smooth curves
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2563eb',
                        pointBorderWidth: 2,
                    }]
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
                            backgroundColor: '#1e293b', // slate-800
                            titleColor: '#94a3b8', // slate-400
                            bodyFont: { weight: 'bold' },
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: ctx => ' $' + ctx.raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                            }
                        }
                    },
                    scales: {
                        x: { display: false }, // Hide fully for clean sparkline look
                        y: { display: false, min: 0 }
                    }
                }
            });
        }
    }
}
</script>
@endpush
