<div>
    <x-ui.page-header title="Renewal Tracker" subtitle="Track upcoming expirations and record renewal payments">
        <div class="flex gap-2">
            {{-- Action buttons if any --}}
        </div>
    </x-ui.page-header>


    {{-- Filters --}}
    <div class="mb-6 p-4 rounded-xl  flex flex-col md:flex-row items-center gap-4">
        <div class="w-full md:flex-1 max-w-sm">
            <x-ui.form-input 
                label="" 
                model="search" 
                placeholder="Search domain, provider, or project..." 
                class="input-sm"
            >
                <x-slot name="prefix">
                    <x-icon-search class="w-4 h-4 text-slate-400" />
                </x-slot>
            </x-ui.form-input>
        </div>
        
        <div class="w-full md:w-auto">
            <select wire:model.live="statusFilter" class="select select-bordered select-sm w-full md:w-48">
                <option value="">All Statuses</option>
                @foreach(\App\Enums\SubscriptionStatus::cases() as $status)
                    @if($status !== \App\Enums\SubscriptionStatus::Cancelled)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>

    @if($this->subscriptions->isEmpty())
        <x-ui.empty-state 
            icon="calendar-off" 
            title="No renewals found" 
            message="There are no active subscriptions matching your criteria."
        />
    @else
        <x-ui.data-table :headers="['Project / Service', 'Provider', 'Cost', 'Expiry', 'Status', 'Actions']">
            @foreach($this->subscriptions as $sub)
                <tr wire:key="sub-{{ $sub->id }}">
                    <td>
                        <div class="font-bold">
                            @if($sub->project)
                                <a href="{{ route('projects.show', $sub->project_id) }}" class="hover:text-primary hover:underline transition-colors block" wire:navigate>
                                    {{ $sub->project->project_name }}
                                </a>
                            @else
                                <span class="text-slate-400 italic">No Project</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500">{{ $sub->domain_name ?: $sub->service_type->label() }}</div>
                    </td>
                    <td>{{ $sub->provider?->name }}</td>
                    <td>${{ number_format($sub->renewal_cost_usd, 2) }}</td>
                    <td>
                        <div class="{{ $sub->days_until_expiry <= 7 ? 'text-red-600 font-bold' : ($sub->days_until_expiry <= 30 ? 'text-orange-500' : '') }}">
                            {{ $sub->expiry_date->format('M d, Y') }}
                        </div>
                        <div class="text-[10px] uppercase text-slate-400">
                            {{ $sub->days_until_expiry }} days left
                        </div>
                    </td>
                    <td>
                        <x-ui.badge-status :status="$sub->status" />
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('mail-mailer.index', ['clientId' => $sub->project?->client_id, 'subscriptionId' => $sub->id, 'template' => 'subscription-reminder']) }}" 
                               class="flex items-center gap-2 btn btn-ghost btn-sm hover:text-orange-500" title="Send Renewal Reminder" wire:navigate>
                                <x-icon-bell class="w-4 h-4" /> Reminder
                            </a>
                            <a href="{{ route('mail-mailer.index', ['clientId' => $sub->project?->client_id, 'subscriptionId' => $sub->id]) }}" 
                               class="flex items-center gap-2 btn btn-ghost btn-sm hover:text-blue-500" title="Send Custom Email" wire:navigate>
                                <x-icon-mail class="w-4 h-4" /> Custom Email
                            </a>
                            <button 
                                type="button"
                                wire:click="openRenewalModal({{ $sub->id }})" 
                                class="btn btn-sm btn-primary ml-1 flex items-center gap-2"
                            >
                                <x-icon-refresh class="w-4 h-4" wire:loading.remove wire:target="openRenewalModal({{ $sub->id }})" />
                                <span class="loading loading-spinner loading-xs" wire:loading wire:target="openRenewalModal({{ $sub->id }})"></span>
                                <span>Renew</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            {{ $this->subscriptions->links() }}
        </div>
    @endif

    {{-- Renewal Modal --}}
    @if($showRenewalModal)
    <div class="fixed inset-0 z-50" x-data @keydown.escape.window="$wire.set('showRenewalModal', false)">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="$wire.set('showRenewalModal', false)"></div>

        {{-- Content --}}
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Process Renewal</h3>
                        <button wire:click="$set('showRenewalModal', false)" class="btn btn-sm btn-circle btn-ghost"><x-icon-x class="w-4 h-4" /></button>
                    </div>
                    <div class="p-6 space-y-6">
                        @php 
                            $subToRenew = $renewingSubscriptionId ? collect($this->subscriptions->items())->firstWhere('id', $renewingSubscriptionId) : null; 
                        @endphp
                        
                        @if($subToRenew)
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                <p class="text-sm font-bold text-slate-800">{{ $subToRenew->domain_name ?: $subToRenew->service_type->label() }}</p>
                                <p class="text-xs text-slate-500 mt-1">Current Expiry: <span class="font-semibold text-slate-700">{{ $subToRenew->expiry_date->format('M d, Y') }}</span></p>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <x-ui.form-select 
                                label="Renewal Method" 
                                model="renewalMode" 
                                :live="true"
                                :options="['years' => 'Add Years to Current Expiry', 'date' => 'Set Fixed Custom Expiry Date']"
                            />

                            @if($renewalMode === 'years')
                                <x-ui.form-select 
                                    label="How many years to add?" 
                                    model="renewalYears" 
                                    :options="['1' => '1 Year', '2' => '2 Years', '3' => '3 Years', '4' => '4 Years', '5' => '5 Years', '10' => '10 Years']"
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
                            <span wire:loading.remove>Confirm Renewal</span>
                            <span wire:loading>
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
