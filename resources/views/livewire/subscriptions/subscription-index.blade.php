<div>
    <x-ui.page-header title="Subscriptions" subtitle="Manage domains, hosting, and service expiries">
        <a href="{{ route('subscriptions.create') }}" class="btn btn-primary btn-sm" wire:navigate>
            <x-icon-plus class="w-4 h-4 mr-1" /> Add Subscription
        </a>
    </x-ui.page-header>


    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <x-icon-search class="h-4 w-4 text-slate-400" />
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" 
                class="input input-bordered w-full pl-10" 
                placeholder="Search domain or provider...">
        </div>
        
        <select wire:model.live="filterService" class="select select-bordered w-full md:w-48">
            <option value="">All Services</option>
            @foreach(\App\Enums\ServiceType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStatus" class="select select-bordered w-full md:w-48">
            <option value="">All Statuses</option>
            @foreach(\App\Enums\SubscriptionStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    @if($this->subscriptions->isEmpty())
        <x-ui.empty-state 
            title="No subscriptions found" 
            message="Start tracking your domains and hosting services today."
            icon="icon-calendar-off"
        />
    @else
        <x-ui.data-table :headers="['Project / Client', 'Service / Domain', 'Type', 'Expiry', 'Status', '']">
            @foreach($this->subscriptions as $sub)
                <tr>
                    <td>
                        <div class="flex flex-col">
                            @if($sub->project)
                                <a href="{{ route('projects.show', $sub->project) }}" class="font-bold text-primary hover:text-blue-600 hover:underline transition-colors w-fit" wire:navigate>
                                    {{ $sub->project->project_name }}
                                </a>
                                @if($sub->project->client)
                                    <a href="{{ route('clients.show', $sub->project->client) }}" class="text-xs text-secondary hover:text-blue-600 hover:underline transition-colors w-fit mt-0.5" wire:navigate>
                                        {{ $sub->project->client->name }}
                                    </a>
                                @else
                                    <span class="text-xs text-secondary mt-0.5 italic">Unknown Client</span>
                                @endif
                            @else
                                <span class="font-bold text-slate-400">Project Not Found</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="flex flex-col">
                            <span class="font-medium text-slate-900">{{ $sub->domain_name ?? 'N/A' }}</span>
                            <span class="text-xs text-slate-500">{{ $sub->provider?->name }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="text-sm">{{ $sub->service_type->label() }}</span>
                    </td>
                    <td>
                        <div class="flex flex-col">
                            <span class="text-sm {{ $sub->traffic_light === 'critical' ? 'text-error font-bold' : ($sub->traffic_light === 'warning' ? 'text-warning font-medium' : 'text-slate-600') }}">
                                {{ $sub->expiry_date?->format('M d, Y') ?? 'No Date' }}
                            </span>
                            <span class="text-[10px] uppercase font-bold tracking-tight {{ $sub->traffic_light === 'critical' ? 'text-error' : ($sub->traffic_light === 'warning' ? 'text-warning' : 'text-slate-400') }}">
                                @if($sub->days_until_expiry < 0)
                                    EXPIRED {{ abs($sub->days_until_expiry) }} DAYS AGO
                                @else
                                    {{ $sub->days_until_expiry }} DAYS LEFT
                                @endif
                            </span>
                        </div>
                    </td>
                    <td>
                        <x-ui.badge-status :status="$sub->status" />
                    </td>
                    <td class="text-right">
                        <x-ui.action-menu 
                            editRoute="{{ route('subscriptions.edit', $sub) }}"
                            deleteAction="confirmDelete({{ $sub->id }})"
                        />
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
        message="Are you sure you want to delete this subscription? This action cannot be undone."
        confirmAction="delete"
    />
</div>
