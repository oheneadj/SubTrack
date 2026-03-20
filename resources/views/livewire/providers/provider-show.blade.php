<div>
    <x-ui.page-header :title="$provider->name" subtitle="Provider Details & Subscriptions">
        <div class="flex gap-2">
            <a href="{{ route('providers.index') }}" class="btn btn-ghost btn-sm" wire:navigate>
                <x-icon-arrow-left class="w-4 h-4 mr-1" /> Back to Providers
            </a>
            <a href="{{ route('subscriptions.create') }}" class="btn btn-primary btn-sm" wire:navigate>
                <x-icon-plus class="w-4 h-4 mr-1" /> Add Subscription
            </a>
        </div>
    </x-ui.page-header>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-ui.stat-card 
            label="Total Services" 
            :value="$this->stats['total_subscriptions']" 
            icon="list-details" 
            variant="neutral"
        />
        <x-ui.stat-card 
            label="Active Services" 
            :value="$this->stats['active_subscriptions']" 
            icon="check" 
            variant="healthy"
        />
        <x-ui.stat-card 
            label="Total Value" 
            :value="'$' . number_format($this->stats['total_value'], 2)" 
            icon="currency-dollar" 
            variant="info"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column: Subscriptions --}}
        <div class="lg:col-span-2 space-y-8">
           
                <div class="p-2 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Provided Services</h3>
                </div>
                
                @if($this->subscriptions->isEmpty())
                    <x-ui.empty-state 
                        icon="list-details" 
                        title="No subscriptions" 
                        message="This provider doesn't have any active subscriptions yet."
                    >
                    </x-ui.empty-state>
                @else
                    <x-ui.data-table :headers="['Service', 'Project / Client', 'Status', 'Expiry', '']">
                        @foreach($this->subscriptions as $sub)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td>
                                    <div class="font-bold text-primary">{{ $sub->service_name ?? $sub->service_type->label() }}</div>
                                    <div class="text-xs text-secondary truncate max-w-xs">{{ $sub->domain_name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    @if($sub->project && $sub->project->client)
                                        <a href="{{ route('projects.show', $sub->project) }}" class="text-sm font-bold text-slate-800 hover:text-blue-600 transition-colors block" wire:navigate>
                                            {{ $sub->project->project_name }}
                                        </a>
                                        <a href="{{ route('clients.show', $sub->project->client) }}" class="text-xs text-slate-500 hover:text-blue-600 transition-colors" wire:navigate>
                                            {{ $sub->project->client->name }}
                                        </a>
                                    @else
                                        <div class="space-y-1">
                                            @if($sub->project)
                                                <a href="{{ route('projects.show', $sub->project) }}" class="text-sm font-bold text-slate-800 hover:text-blue-600 transition-colors block" wire:navigate>
                                                    {{ $sub->project->project_name }}
                                                </a>
                                                <span class="text-xs text-red-400 font-medium italic">Client Not Found</span>
                                            @else
                                                <span class="text-sm font-bold text-red-500 italic">Project Not Found</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.badge-status :status="$sub->status->value" />
                                </td>
                                <td class="text-secondary text-sm">
                                    {{ $sub->expiry_date->format('M d, Y') }}
                                </td>
                                <td class="text-right">
                                    <x-ui.action-menu 
                                        editAction="window.location.href='{{ route('subscriptions.edit', $sub) }}'" 
                                        deleteAction="$wire.deleteSubscription({{ $sub->id }})" 
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                    <div class="p-4 border-t border-slate-100">
                        {{ $this->subscriptions->links() }}
                    </div>
                @endif
            
        </div>

        {{-- Right Column: Provider Info --}}
        <div class="space-y-8">
            {{-- Provider Details --}}
            <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Provider Info</h3>
                
                <div class="space-y-6">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Provider Name</label>
                        <p class="text-slate-800 text-base font-bold">{{ $provider->name }}</p>
                    </div>

                    @if($provider->website)
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Website</label>
                        <a href="{{ $provider->website }}" target="_blank" class="text-blue-600 hover:underline text-sm font-medium flex items-center gap-1">
                            {{ $provider->website }} <x-icon-world class="w-3 h-3" />
                        </a>
                    </div>
                    @endif
                    
                    @if($provider->support_email)
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Support Email</label>
                        <a href="mailto:{{ $provider->support_email }}" class="text-slate-800 text-sm font-medium flex items-center gap-2 hover:text-blue-600 transition-colors">
                            <x-icon-mail class="w-4 h-4 text-slate-400" />
                            {{ $provider->support_email }}
                        </a>
                    </div>
                    @endif

                    <div class="pt-4 border-t border-slate-100">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Added On</label>
                        <p class="text-slate-500 text-sm font-medium">{{ $provider->created_at->format('F d, Y') }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
