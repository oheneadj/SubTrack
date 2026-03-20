<div>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="flex items-center gap-2 mt-3 sm:mt-0">
            <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm" wire:navigate>
                <x-icon-plus class="w-4 h-4 mr-1" /> New Invoice
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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

        {{-- Monthly Revenue Chart --}}
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800 text-sm">Monthly Revenue</h3>
                <div class="flex items-center gap-1.5">
                    @if($revenueChange['direction'] === 'up')
                        <span class="text-green-600 text-xs font-bold flex items-center gap-0.5">
                            <x-icon-arrow-up class="w-3 h-3" /> {{ $revenueChange['percentage'] }}%
                        </span>
                    @else
                        <span class="text-red-600 text-xs font-bold flex items-center gap-0.5">
                            <x-icon-arrow-down class="w-3 h-3" /> {{ abs($revenueChange['percentage']) }}%
                        </span>
                    @endif
                    <span class="text-[10px] text-slate-400">vs last month</span>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-900 mb-4">${{ number_format($revenueChange['current'], 2) }}</p>

            <div
                x-data="revenueChart({{ Js::from($revenueData) }})"
                x-init="init()"
            >
                <div style="position:relative; height:120px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
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
            const last   = values.length - 1;

            new Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: values.map((_, i) =>
                            i === last ? '#2563eb' : 'rgba(37,99,235,0.10)'
                        ),
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' $' + ctx.raw.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 } } },
                        y: { display: false }
                    }
                }
            });
        }
    }
}
</script>
@endpush
