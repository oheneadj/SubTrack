<div>
    <x-ui.page-header title="Subscriptions" subtitle="{{ $showTrash ? 'Deleted subscriptions — restore one to bring it back' : 'Manage domains, hosting, and service expiries' }}">
        <div class="flex items-center gap-2">
            <x-ui.button variant="ghost" wire:click="toggleTrash" class="whitespace-nowrap">
                @if($showTrash)
                    <x-icon-arrow-left class="w-4 h-4" />
                    <span>Back to Subscriptions</span>
                @else
                    <x-icon-trash class="w-4 h-4" />
                    <span>Trash</span>
                @endif
            </x-ui.button>
            @unless($showTrash)
                <x-ui.button as="a" href="{{ route('subscriptions.create') }}" wire:navigate>
                    <x-icon-plus class="w-4 h-4" />
                    <span>Add Subscription</span>
                </x-ui.button>
            @endunless
        </div>
    </x-ui.page-header>

    <x-ui.toolbar searchModel="search" searchPlaceholder="Search domain, provider, client, or project...">
        <select wire:model.live="filterService" class="select select-bordered shrink-0 w-full md:w-44">
            <option value="">All Services</option>
            @foreach(\App\Enums\ServiceType::cases() as $type)
                <option wire:key="type-{{ $type->value }}" value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStatus" class="select select-bordered shrink-0 w-full md:w-44">
            <option value="">All Statuses</option>
            @foreach(\App\Enums\SubscriptionStatus::cases() as $status)
                <option wire:key="status-{{ $status->value }}" value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterRenewalType" class="select select-bordered shrink-0 w-full md:w-44">
            <option value="">All Renewal Types</option>
            @foreach(\App\Enums\SubscriptionRenewalType::cases() as $type)
                <option wire:key="renewal-type-{{ $type->value }}" value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="filterRenewalFrom" class="input input-bordered shrink-0 w-full md:w-36" title="Renewal date from">
        <input type="date" wire:model.live="filterRenewalTo" class="input input-bordered shrink-0 w-full md:w-36" title="Renewal date to">

        <x-ui.button variant="secondary" soft wire:click="export" class="shrink-0 whitespace-nowrap">
            <x-icon-file-invoice class="w-4 h-4" />
            <span>Export CSV</span>
        </x-ui.button>
    </x-ui.toolbar>

    @if(!$showTrash && count($selectedSubscriptions) > 0)
        <div class="flex items-center justify-between bg-primary/10 border border-primary/20 p-4 rounded-xl mb-6 animate-in fade-in slide-in-from-top-4">
            <div class="flex items-center gap-4">
                <span class="text-sm font-bold text-primary">{{ count($selectedSubscriptions) }} selected</span>
                <div class="h-4 w-px bg-primary/20"></div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Bulk Actions:</span>
                    <x-ui.button variant="success" size="xs" wire:click="applyBulkStatus('Active')" wire:loading.attr="disabled" class="font-bold text-white">
                        <span wire:loading.remove>Mark Active</span>
                        <span wire:loading><span class="loading loading-spinner loading-xs"></span></span>
                    </x-ui.button>
                    <x-ui.button variant="error" size="xs" wire:click="applyBulkStatus('Cancelled')" wire:loading.attr="disabled" class="font-bold text-white">
                        <span wire:loading.remove>Mark Cancelled</span>
                        <span wire:loading><span class="loading loading-spinner loading-xs"></span></span>
                    </x-ui.button>
                </div>
            </div>
            <x-ui.button variant="ghost" size="xs" circle @click="$wire.set('selectedSubscriptions', [])" class="text-slate-400 hover:text-error">
                <x-icon-x class="w-4 h-4" />
            </x-ui.button>
        </div>
    @endif

    @if($this->subscriptions->isEmpty())
        <x-ui.empty-state
            title="{{ $showTrash ? 'Trash is empty' : 'No subscriptions found' }}"
            message="{{ $showTrash ? 'Deleted subscriptions will show up here.' : 'Start tracking your domains and hosting services today.' }}"
            icon="{{ $showTrash ? 'trash' : 'icon-calendar-off' }}"
        />
    @else
        <x-ui.data-table :headers="['client_name' => 'Project / Client', 'domain_name' => 'Service / Domain', 'service_type' => 'Type', 'expiry_date' => 'Expiry', 'status' => 'Status', '']" :sortColumn="$sortColumn" :sortDirection="$sortDirection" :selectable="!$showTrash">
            @foreach($this->subscriptions as $sub)
                <tr wire:key="sub-{{ $sub->ulid }}" class="{{ in_array($sub->ulid, $selectedSubscriptions) ? 'bg-primary/5' : '' }}">
                    @unless($showTrash)
                        <td class="w-10 px-4">
                            <input type="checkbox" wire:model.live="selectedSubscriptions" value="{{ $sub->ulid }}" class="checkbox checkbox-sm checkbox-primary" />
                        </td>
                    @endunless
                    <td>
                        <div class="flex flex-col">
                            @if($sub->project)
                                <a href="{{ route('projects.show', $sub->project) }}" class="font-bold text-primary hover:text-blue-600 hover:underline transition-colors w-fit" wire:navigate>
                                    {{ $sub->project->project_name }}
                                </a>
                            @else
                                <span class="font-bold text-slate-400 w-fit">Unrelated</span>
                            @endif
                            @if($sub->effective_client)
                                <a href="{{ route('clients.show', $sub->effective_client) }}" class="text-xs text-secondary hover:text-blue-600 hover:underline transition-colors w-fit mt-0.5" wire:navigate>
                                    {{ $sub->effective_client->name }}
                                </a>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($showTrash)
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-500">{{ $sub->domain_name ?? 'N/A' }}</span>
                                <span class="text-xs text-slate-500">{{ $sub->provider?->name }}</span>
                            </div>
                        @else
                            <a href="{{ route('subscriptions.show', $sub) }}" class="flex flex-col group" wire:navigate>
                                <span class="font-medium text-slate-900 group-hover:text-primary group-hover:underline transition-colors">{{ $sub->domain_name ?? 'N/A' }}</span>
                                <span class="text-xs text-slate-500">{{ $sub->provider?->name }}</span>
                            </a>
                        @endif
                    </td>
                    <td>
                        <span class="text-sm">{{ $sub->service_type->label() }}</span>
                    </td>
                    <td>
                        @if($showTrash)
                            <span class="text-sm text-slate-500">Deleted {{ $sub->deleted_at->format('M d, Y') }}</span>
                        @else
                            <div class="flex flex-col">
                                <span class="text-sm {{ $sub->traffic_light === 'critical' ? 'text-error font-bold' : ($sub->traffic_light === 'warning' ? 'text-warning font-medium' : 'text-slate-600') }}">
                                    {{ $sub->expiry_date?->format('M d, Y') ?? 'No Date' }}
                                </span>
                                <span class="text-[10px] uppercase font-bold tracking-tight {{ $sub->traffic_light === 'critical' ? 'text-error' : ($sub->traffic_light === 'warning' ? 'text-warning' : 'text-slate-400') }}">
                                    @if($sub->days_until_expiry < 0)
                                        @if($sub->missed_payments_count)
                                            {{ $sub->missed_payments_count }} PAYMENT{{ $sub->missed_payments_count > 1 ? 'S' : '' }} MISSED
                                        @else
                                            EXPIRED {{ abs($sub->days_until_expiry) }} DAYS AGO
                                        @endif
                                    @else
                                        {{ $sub->days_until_expiry }} DAYS LEFT
                                    @endif
                                </span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <x-ui.badge-status :status="$sub->status" />
                    </td>
                    <td class="text-right">
                        @if($showTrash)
                            <x-ui.button wire:click="restore('{{ $sub->ulid }}')" variant="soft" size="xs">
                                <x-icon-refresh class="w-3.5 h-3.5" />
                                <span>Restore</span>
                            </x-ui.button>
                        @else
                            <x-ui.action-menu
                                viewAction="{{ route('subscriptions.show', $sub) }}"
                                editAction="window.location.href='{{ route('subscriptions.edit', $sub) }}'"
                                deleteAction="confirmDelete('{{ $sub->ulid }}')"
                            />
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            {{ $this->subscriptions->links() }}
        </div>
    @endif

    <x-ui.confirm-modal 
        id="confirm-delete-subscription"
        title="Delete Subscription"
        message="Are you sure you want to delete this subscription? You can restore it later from the Trash if needed."
        confirmAction="delete"
    />
</div>
