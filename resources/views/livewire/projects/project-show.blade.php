<div>
    {{-- Page Header --}}
    <x-ui.page-header :title="$project->project_name" :subtitle="'Project for ' . ($project->client?->name ?? 'Unknown Client')">
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.index') }}" class="btn btn-ghost btn-sm flex items-center gap-2" wire:navigate>
                <x-icon-arrow-left class="w-4 h-4" />
                <span>Back to Projects</span>
            </a>

            <a href="{{ route('subscriptions.create', ['projectId' => $project->id]) }}" class="btn btn-primary btn-sm flex items-center gap-2" wire:navigate>
                <x-icon-plus class="w-4 h-4" />
                <span>Add Subscription</span>
            </a>

            <a href="{{ route('invoices.create', ['clientId' => $project->client_id, 'projectId' => $project->id]) }}" class="btn btn-secondary btn-sm bg-slate-800 hover:bg-slate-700 text-white border-0 flex items-center gap-2" wire:navigate>
                <x-icon-file-invoice class="w-4 h-4" />
                <span>New Invoice</span>
            </a>

            <button 
                @click="$dispatch('open-modal', { id: 'project-modal' })" 
                wire:click="$dispatchTo('projects.project-form', 'open-project-modal', { id: {{ $project->id }} })" 
                class="btn btn-soft btn-sm border-slate-200 bg-white flex items-center gap-2">
                <x-icon-edit class="w-4 h-4" />
                <span>Edit Project</span>
            </button>
        </div>
    </x-ui.page-header>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
            label="Expiring Soon" 
            :value="$this->stats['expiring_soon']" 
            icon="alert-circle" 
            variant="warning"
        />
        <x-ui.stat-card 
            label="Est. Value" 
            :value="'$' . number_format($this->stats['total_value'], 2)" 
            icon="currency-dollar" 
            variant="info"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column: Subscriptions --}}
        <div class="lg:col-span-2 space-y-8">
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Project Subscriptions</h3>
                </div>
                
                @if($this->subscriptions->isEmpty())
                    <x-ui.empty-state 
                        icon="list-details" 
                        title="No subscriptions" 
                        message="This project doesn't have any active subscriptions yet."
                    >
                        <a href="{{ route('subscriptions.create', ['projectId' => $project->id]) }}" class="btn btn-primary btn-sm">Add Subscription</a>
                    </x-ui.empty-state>
                @else
                    <x-ui.data-table :headers="['Service', 'Provider', 'Status', 'Expiry', '']">
                        @foreach($this->subscriptions as $sub)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td>
                                    <div class="font-bold text-primary">{{ $sub->service_name ?? $sub->service_type->label() }}</div>
                                    <div class="text-xs text-secondary truncate max-w-xs">{{ $sub->domain_name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="text-sm font-medium">{{ $sub->provider?->name }}</span>
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
                                        deleteAction="confirmDelete({{ $sub->id }})" 
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                    <div class="p-4 border-t border-slate-100">
                        {{ $this->subscriptions->links() }}
                    </div>
                @endif
            </section>
        </div>

        {{-- Right Column: Project & Client Info --}}
        <div class="space-y-8">
            {{-- Project Details --}}
            <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Project Details</h3>
                
                <div class="space-y-6">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Description</label>
                        <p class="text-slate-800 text-sm leading-relaxed">{{ $project->description ?: 'No description provided.' }}</p>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Created</label>
                        <p class="text-slate-800 text-sm font-medium">{{ $project->created_at->format('F d, Y') }}</p>
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Last Updated</label>
                        <p class="text-slate-800 text-sm font-medium">{{ $project->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </section>

            {{-- Client Info --}}
            <section class="bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-200 p-6 shadow-sm relative overflow-hidden group">
                <!-- Decorative element -->
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-colors duration-500"></div>
                
                <h3 class="text-lg font-bold text-slate-800 mb-6 relative z-10">Client Information</h3>
                
                <div class="flex items-center gap-4 mb-6 relative z-10">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                        {{ $project->client ? substr($project->client->name, 0, 1) : '?' }}
                    </div>
                    <div>
                        @if($project->client)
                            <a href="{{ route('clients.show', $project->client) }}" class="font-bold text-primary hover:text-blue-600 transition-colors text-lg" wire:navigate>
                                {{ $project->client->name }}
                            </a>
                            <p class="text-xs text-secondary">{{ $project->client->company_name ?? 'Individual Client' }}</p>
                        @else
                            <span class="font-bold text-red-500 italic text-lg">Unknown Client</span>
                            <p class="text-xs text-secondary">This client may have been deleted.</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 relative z-10">
                    <a href="mailto:{{ $project->client->email }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-white border border-transparent hover:border-slate-100 transition-all text-sm text-slate-600 hover:text-blue-600">
                        <x-icon-mail class="w-4 h-4" />
                        <span class="truncate">{{ $project->client->email }}</span>
                    </a>
                    
                    @if($project->client->phone)
                    <a href="tel:{{ $project->client->phone }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-white border border-transparent hover:border-slate-100 transition-all text-sm text-slate-600 hover:text-blue-600">
                        <x-icon-phone class="w-4 h-4" />
                        <span>{{ $project->client->phone }}</span>
                    </a>
                    @endif
                </div>
                
                <div class="mt-6 pt-4 border-t border-slate-100 relative z-10">
                    @if($project->client)
                        <a href="{{ route('clients.show', $project->client) }}" class="btn btn-soft w-full bg-white border-slate-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 transition-colors" wire:navigate>
                            View Client Profile
                        </a>
                    @else
                        <button disabled class="btn btn-soft w-full bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed">
                            Client Profile Unavailable
                        </button>
                    @endif
                </div>
            </section>
        </div>
    </div>
    <x-ui.modal id="project-modal">
        <livewire:projects.project-form :isModal="true" />
    </x-ui.modal>
</div>
