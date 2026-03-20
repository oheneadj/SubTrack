<div>
    <x-ui.page-header 
        title="{{ $isEditing ? 'Edit Subscription' : 'Add Subscription' }}" 
        subtitle="{{ $isEditing ? 'Update service details for ' . $domain_name : 'Track a new domain, hosting, or SSL service' }}"
    />

    <div class="max-w-4xl">
        <div class="card bg-white border border-slate-200 shadow-sm">
            <div class="card-body gap-6">
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Client Selection -->
                        <x-ui.form-select 
                            label="Client" 
                            model="client_id" 
                            :options="$clients->pluck('name', 'id')->toArray()" 
                            placeholder="Select a client"
                            :live="true"
                            required
                        />

                        <!-- Project Selection -->
                        <x-ui.form-select 
                            label="Project" 
                            model="project_id" 
                            :options="$projects->pluck('project_name', 'id')->toArray()" 
                            placeholder="{{ $client_id ? 'Select a project' : 'Select a client first' }}"
                            required
                        />

                        <!-- Service Type -->
                        <x-ui.form-select 
                            label="Service Type" 
                            model="service_type" 
                            :options="collect(\App\Enums\ServiceType::cases())->mapWithKeys(fn($t) => [$t->value => $t->label()])->toArray()"
                            required
                        />

                        <!-- Domain Name / Service Name -->
                        <div class="md:col-span-2">
                            <x-ui.form-input 
                                label="Domain / Service Name" 
                                model="domain_name" 
                                placeholder="e.g. example.com or API Hosting"
                                required
                            />
                        </div>

                        <!-- Provider -->
                        <x-ui.form-select 
                            label="Provider" 
                            model="provider_id" 
                            :options="$providers->pluck('name', 'id')->toArray()"
                            placeholder="Select a provider"
                            required
                        />

                        <!-- Status -->
                        <x-ui.form-select 
                            label="Status" 
                            model="status" 
                            :options="collect(\App\Enums\SubscriptionStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])->toArray()"
                            required
                        />

                        <!-- Costs -->
                        <x-ui.form-input 
                            label="Initial Cost (USD)" 
                            model="purchase_cost_usd" 
                            type="number"
                            step="0.01"
                            prefix="$"
                        />

                        <x-ui.form-input 
                            label="Renewal Cost (USD)" 
                            model="renewal_cost_usd" 
                            type="number"
                            step="0.01"
                            prefix="$"
                        />

                        <!-- Dates -->
                        <x-ui.form-input 
                            label="Purchase Date" 
                            model="purchase_date" 
                            type="date"
                            required
                        />

                        <x-ui.form-input 
                            label="Expiry Date" 
                            model="expiry_date" 
                            type="date"
                            required
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <a href="{{ route('subscriptions.index') }}" class="btn btn-ghost" wire:navigate>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ $isEditing ? 'Update Subscription' : 'Create Subscription' }}</span>
                            <span wire:loading class="flex items-center gap-2">
                                <span class="loading loading-spinner loading-xs"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
