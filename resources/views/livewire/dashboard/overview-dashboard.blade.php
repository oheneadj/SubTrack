<div>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="flex items-center gap-2 mt-3 sm:mt-0">
            <x-ui.button as="a" href="{{ route('invoices.create') }}" wire:navigate class="whitespace-nowrap">
                <x-icon-plus class="w-4 h-4" />
                <span>New Invoice</span>
            </x-ui.button>
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
            :href="route('renewals.index')"
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

    {{-- Finance Summary Section — deliberately minimal. The full breakdown
         (chart, MRR, provider costs, profit, provider/client breakdowns)
         lives on the Finance dashboard; duplicating it here was the exact
         source of the "two dashboards silently drift apart" bug class this
         app kept hitting. These 2 tiles are just enough context to know
         whether to click through. --}}
    <h2 class="text-lg font-bold text-slate-800 tracking-tight mb-4">Finance Summary</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
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

    <div class="text-center mb-8">
        <a href="{{ route('finances.index') }}" class="text-sm font-medium text-blue-600 hover:underline" wire:navigate>View Finance Dashboard for full details &rarr;</a>
    </div>

    {{-- Two Column Section: Critical + Warning Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Critical Expirations --}}
        <x-ui.card :padding="false">
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
                                        <a href="{{ route('subscriptions.show', $sub) }}" class="group block" wire:navigate>
                                            <div class="font-bold text-sm text-slate-800 group-hover:text-blue-600 group-hover:underline transition-colors">{{ $sub->project?->client?->name }}</div>
                                            <div class="text-[11px] text-slate-500 truncate max-w-[140px]">{{ $sub->project?->project_name }}</div>
                                        </a>
                                    </td>
                                    <td>
                                        <x-ui.badge-status :status="$sub->service_type->value" />
                                    </td>
                                    <td>
                                        <x-ui.days-pill :days="$sub->days_until_expiry" :missedPayments="$sub->missed_payments_count" />
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.button as="a" href="{{ route('subscriptions.show', $sub) }}" wire:navigate variant="ghost" circle size="xs" title="View">
                                                <x-icon-eye class="w-3.5 h-3.5" />
                                            </x-ui.button>
                                            <x-ui.button
                                                type="button"
                                                wire:click="sendReminder('{{ $sub->ulid }}')"
                                                wire:loading.attr="disabled"
                                                variant="ghost"
                                                circle
                                                size="xs"
                                                title="Send Reminder"
                                            >
                                                <span wire:loading.remove wire:target="sendReminder('{{ $sub->ulid }}')">
                                                    <x-icon-send class="w-3.5 h-3.5" />
                                                </span>
                                                <span wire:loading wire:target="sendReminder('{{ $sub->ulid }}')">
                                                    <span class="loading loading-spinner loading-xs"></span>
                                                </span>
                                            </x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        {{-- Expiring This Month --}}
        <x-ui.card :padding="false">
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
                                        <a href="{{ route('subscriptions.show', $sub) }}" class="group block" wire:navigate>
                                            <div class="font-bold text-sm text-slate-800 group-hover:text-blue-600 group-hover:underline transition-colors">{{ $sub->project?->client?->name }}</div>
                                            <div class="text-[11px] text-slate-500 truncate max-w-[140px]">{{ $sub->project?->project_name }}</div>
                                        </a>
                                    </td>
                                    <td>
                                        <x-ui.badge-status :status="$sub->service_type->value" />
                                    </td>
                                    <td>
                                        <x-ui.days-pill :days="$sub->days_until_expiry" :missedPayments="$sub->missed_payments_count" />
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.button as="a" href="{{ route('subscriptions.show', $sub) }}" wire:navigate variant="ghost" circle size="xs" title="View">
                                                <x-icon-eye class="w-3.5 h-3.5" />
                                            </x-ui.button>
                                            <x-ui.button
                                                type="button"
                                                wire:click="sendReminder('{{ $sub->ulid }}')"
                                                wire:loading.attr="disabled"
                                                variant="ghost"
                                                circle
                                                size="xs"
                                                title="Send Reminder"
                                            >
                                                <span wire:loading.remove wire:target="sendReminder('{{ $sub->ulid }}')">
                                                    <x-icon-send class="w-3.5 h-3.5" />
                                                </span>
                                                <span wire:loading wire:target="sendReminder('{{ $sub->ulid }}')">
                                                    <span class="loading loading-spinner loading-xs"></span>
                                                </span>
                                            </x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>

    {{-- Three Column Section: Invoices + Revenue + Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Invoices (wider) --}}
        <x-ui.card :padding="false" class="lg:col-span-1">
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
        </x-ui.card>

        {{-- Recent Activity Feed --}}
        <x-ui.card :padding="false">
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
        </x-ui.card>
    </div>

</div>
