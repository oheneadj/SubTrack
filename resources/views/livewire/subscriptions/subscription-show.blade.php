<div>
    <x-ui.page-header
        :title="$subscription->domain_name ?: $subscription->service_type->label()"
        :subtitle="$subscription->service_type->label() . ($this->client ? ' · ' . $this->client->name : '')"
    >
        <div class="flex items-center gap-3">
            <a href="{{ route('subscriptions.index') }}" class="btn btn-ghost btn-sm flex items-center gap-2" wire:navigate>
                <x-icon-arrow-left class="w-4 h-4" />
                <span>Back</span>
            </a>
            <a href="{{ route('subscriptions.edit', $subscription) }}" class="btn btn-soft btn-sm flex items-center gap-2" wire:navigate>
                <x-icon-edit class="w-4 h-4" />
                <span>Edit</span>
            </a>
        </div>
    </x-ui.page-header>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-ui.stat-card
            label="Renewal Cost"
            :value="$subscription->formatted_renewal_cost_usd . '/yr'"
            icon="currency-dollar"
            variant="info"
        />
        <x-ui.stat-card
            label="Days Until Expiry"
            :value="$subscription->days_until_expiry"
            icon="clock"
            :variant="$subscription->days_until_expiry <= 7 ? 'critical' : ($subscription->days_until_expiry <= 30 ? 'warning' : 'healthy')"
        />
        <x-ui.stat-card
            label="Total Renewals"
            :value="$this->stats['total_renewals']"
            icon="refresh"
            variant="neutral"
        />
        <x-ui.stat-card
            label="Total Margin"
            :value="'$' . number_format($this->stats['total_margin'], 2)"
            icon="calculator"
            variant="healthy"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Left: Service Details + Renewal History --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Service Details --}}
            <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Service Details</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Status</label>
                        <x-ui.badge-status :status="$subscription->status->value" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Service Type</label>
                        <p class="text-slate-800 font-medium text-sm">{{ $subscription->service_type->label() }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Renewal Type</label>
                        <p class="text-slate-800 font-medium text-sm">{{ $subscription->renewal_type->label() }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Provider</label>
                        <p class="text-slate-800 font-medium text-sm">{{ $subscription->provider?->name ?? '—' }}</p>
                    </div>
                    @if($subscription->domain_name)
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Domain / Name</label>
                        <p class="text-slate-800 font-medium text-sm font-mono">{{ $subscription->domain_name }}</p>
                    </div>
                    @endif
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Purchase Date</label>
                        <p class="text-slate-800 text-sm">{{ $subscription->purchase_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Expiry Date</label>
                        <p class="text-sm font-bold {{ $subscription->days_until_expiry <= 7 ? 'text-red-600' : ($subscription->days_until_expiry <= 30 ? 'text-orange-500' : 'text-slate-800') }}">
                            {{ $subscription->expiry_date->format('M d, Y') }}
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Purchase Cost</label>
                        <p class="text-slate-800 text-sm font-semibold">{{ $subscription->formatted_purchase_cost_usd }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Provider Cost</label>
                        <p class="text-slate-800 text-sm font-semibold">{{ $subscription->formatted_renewal_cost_usd }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Markup</label>
                        <p class="text-slate-800 text-sm font-semibold">{{ $subscription->markup_percentage ? $subscription->markup_percentage . '%' : '—' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Client Cost</label>
                        <p class="text-slate-800 text-sm font-semibold">{{ $subscription->formatted_client_renewal_cost_usd }}</p>
                    </div>
                </div>
            </section>

            {{-- Notes --}}
            <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Notes</h3>
                <form wire:submit="saveNotes" class="space-y-3">
                    <x-ui.form-textarea
                        model="notes"
                        placeholder="Any context worth keeping about this subscription..."
                        :rows="3"
                    />
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-soft btn-sm" wire:loading.attr="disabled" wire:target="saveNotes">
                            <span wire:loading.remove wire:target="saveNotes">Save Notes</span>
                            <span wire:loading wire:target="saveNotes">Saving...</span>
                        </button>
                    </div>
                </form>
            </section>

            {{-- Renewal History --}}
            <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Renewal History</h3>
                    <a href="{{ route('renewals.index') }}" class="text-xs text-primary font-semibold hover:underline" wire:navigate>
                        Go to Renewal Tracker
                    </a>
                </div>

                @if($this->renewals->isEmpty())
                    <x-ui.empty-state
                        icon="refresh"
                        title="No renewals yet"
                        message="Renewals will appear here once this subscription is processed."
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th class="bg-slate-50/50 text-xs">Due Date</th>
                                    <th class="bg-slate-50/50 text-xs">Provider Cost</th>
                                    <th class="bg-slate-50/50 text-xs">Client Cost</th>
                                    <th class="bg-slate-50/50 text-xs">Margin</th>
                                    <th class="bg-slate-50/50 text-xs">Status</th>
                                    <th class="bg-slate-50/50 text-xs">Invoice</th>
                                    <th class="bg-slate-50/50 text-xs">Confirmed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->renewals as $renewal)
                                    <tr wire:key="renewal-{{ $renewal->id }}" class="hover:bg-slate-50/50">
                                        <td class="text-sm font-medium text-slate-700">
                                            {{ $renewal->due_date->format('M d, Y') }}
                                        </td>
                                        <td class="text-sm text-slate-600">{{ $renewal->formatted_provider_cost_usd }}</td>
                                        <td class="text-sm font-semibold text-slate-800">{{ $renewal->formatted_client_cost_usd }}</td>
                                        <td class="text-sm {{ $renewal->margin >= 0 ? 'text-green-600' : 'text-red-500' }} font-semibold">
                                            {{ $renewal->formatted_margin }}
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $renewal->payment_status->color() }} badge-soft text-xs">
                                                {{ $renewal->payment_status->label() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($renewal->invoice)
                                                <a href="{{ route('invoices.edit', $renewal->invoice) }}" class="text-xs text-primary hover:underline font-mono" wire:navigate>
                                                    {{ $renewal->invoice->invoice_number }}
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-slate-500">
                                            {{ $renewal->renewal_confirmed_date?->format('M d, Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        {{-- Right: Actions + Linked To --}}
        <div class="space-y-6">

            {{-- Quick Actions --}}
            <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    @if($this->client)
                        <a href="{{ route('mail-mailer.index', ['clientId' => $this->client->ulid, 'subscriptionId' => $subscription->ulid, 'template' => 'subscription-reminder']) }}"
                           class="btn btn-warning btn-sm w-full flex items-center gap-2" wire:navigate>
                            <x-icon-bell class="w-4 h-4" />
                            Send Renewal Reminder
                        </a>
                    @endif
                    @if($subscription->renewal_type->isRecurring())
                        <button wire:click="openRenewalModal" class="btn btn-primary btn-sm w-full flex items-center gap-2">
                            <x-icon-refresh class="w-4 h-4" />
                            Process Renewal
                        </button>
                    @endif
                    <a href="{{ route('subscriptions.edit', $subscription) }}" class="btn btn-ghost btn-sm w-full flex items-center gap-2" wire:navigate>
                        <x-icon-edit class="w-4 h-4" />
                        Edit Subscription
                    </a>
                </div>
            </section>

            {{-- Client / Project --}}
            <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Linked To</h3>
                <div class="space-y-4">
                    @if($this->client)
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Client</label>
                        <a href="{{ route('clients.show', $this->client) }}" class="text-sm font-bold text-primary hover:underline" wire:navigate>
                            {{ $this->client->name }}
                        </a>
                        @if($this->client->company_name)
                            <p class="text-xs text-slate-400">{{ $this->client->company_name }}</p>
                        @endif
                    </div>
                    @endif
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Project</label>
                        @if($subscription->project)
                            <a href="{{ route('projects.show', $subscription->project) }}" class="text-sm font-bold text-primary hover:underline" wire:navigate>
                                {{ $subscription->project->project_name }}
                            </a>
                        @else
                            <p class="text-sm font-bold text-slate-500">Unrelated</p>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

    @if(session('success'))
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition>
            <div class="alert alert-success shadow-lg rounded-xl">
                <x-icon-check class="w-5 h-5" />
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Renewal Modal --}}
    @if($showRenewalModal)
    <div class="fixed inset-0 z-50" x-data @keydown.escape.window="$wire.set('showRenewalModal', false)">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="$wire.set('showRenewalModal', false)"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Process Renewal</h3>
                        <button wire:click="$set('showRenewalModal', false)" class="btn btn-sm btn-circle btn-ghost">
                            <x-icon-x class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-sm font-bold text-slate-800">{{ $subscription->domain_name ?: $subscription->service_type->label() }}</p>
                            <p class="text-xs text-slate-500 mt-1">Current Expiry: <span class="font-semibold text-slate-700">{{ $subscription->expiry_date->format('M d, Y') }}</span></p>
                        </div>
                        <div class="space-y-4">
                            <div class="form-control w-full">
                                <label class="label"><span class="label-text font-bold text-slate-700">Provider Cost (USD)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" wire:model="renewalProviderCost"
                                        class="input input-bordered w-full focus:input-primary transition-all @error('renewalProviderCost') input-error @enderror">
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Client will be charged:
                                    <span class="font-semibold text-slate-800">
                                        ${{ number_format($renewalProviderCost * (1 + ($subscription->markup_percentage ?? 0) / 100), 2) }}
                                    </span>
                                    @if($subscription->markup_percentage)
                                        ({{ $subscription->markup_percentage }}% markup)
                                    @endif
                                </p>
                                @if((int) round($renewalProviderCost * 100) !== (int) $subscription->renewal_cost_usd)
                                    <p class="text-xs text-orange-500 mt-1">
                                        Differs from current provider cost ({{ $subscription->formatted_renewal_cost_usd }}) — subscription price will be updated.
                                    </p>
                                @endif
                                @error('renewalProviderCost') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <x-ui.form-select
                                label="Renewal Method"
                                model="renewalMode"
                                :live="true"
                                :options="['years' => 'Add Years to Current Expiry', 'months' => 'Add Months to Current Expiry', 'date' => 'Set Fixed Custom Expiry Date']"
                            />
                            @if($renewalMode === 'years')
                                <x-ui.form-select
                                    label="How many years to add?"
                                    model="renewalYears"
                                    :options="['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '4' => '4 Years', '5' => '5 Years', '10' => '10 Years']"
                                />
                            @elseif($renewalMode === 'months')
                                <x-ui.form-select
                                    label="How many months to add?"
                                    model="renewalMonths"
                                    :options="['1' => '1 Month', '3' => '3 Months', '6' => '6 Months', '12' => '12 Months']"
                                />
                            @else
                                <div class="form-control w-full">
                                    <label class="label"><span class="label-text font-bold text-slate-700">Next Expiry Date</span></label>
                                    <input type="date" wire:model="customExpiryDate" class="input input-bordered w-full focus:input-primary transition-all @error('customExpiryDate') input-error @enderror">
                                    @error('customExpiryDate') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex justify-end gap-3">
                        <button wire:click="$set('showRenewalModal', false)" class="btn btn-ghost btn-sm">Cancel</button>
                        <button wire:click="processRenewal" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="processRenewal">Confirm Renewal</span>
                            <span wire:loading wire:target="processRenewal">
                                <span class="loading loading-spinner loading-xs"></span> Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
