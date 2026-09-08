<div>
    {{-- Page Header --}}
    <x-ui.page-header :title="$client->name" :subtitle="'Relationship overview for ' . ($client->company_name ?? $client->name)">
        <div class="flex items-center gap-3">
            <x-ui.button as="a" variant="ghost" href="{{ route('clients.index') }}">
                <x-icon-arrow-left class="w-4 h-4" />
                <span>Back to List</span>
            </x-ui.button>

            @if(session('success'))
                <div class="alert alert-success py-2 px-4 mb-0 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="relative" x-data="{ open: false }">
                <x-ui.button @click="open = !open" type="button" soft class="bg-white hover:bg-slate-50 border-slate-200">
                    <x-icon-mail class="w-4 h-4 text-blue-500" />
                    <span>Communication</span>
                </x-ui.button>
                <ul x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 menu p-2 shadow-xl bg-white border border-slate-200 rounded-xl w-56 mt-2">
                    <li>
                        <a href="{{ route('mail-mailer.index', ['clientId' => $client->id]) }}" class="flex items-center gap-2 py-2 px-3 hover:bg-slate-50 rounded-lg text-sm text-primary transition-colors">
                            <x-icon-send class="w-4 h-4 text-blue-400" />
                            <span class="font-medium">Send Custom Email</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('mail-mailer.index', ['clientId' => $client->id, 'template' => 'subscription-reminder']) }}" class="flex items-center gap-2 py-2 px-3 hover:bg-slate-50 rounded-lg text-sm text-primary transition-colors">
                            <x-icon-refresh class="w-4 h-4 text-orange-400" />
                            <span class="font-medium">Send Renewal Reminder</span>
                        </a>
                    </li>
                </ul>
            </div>

            <x-ui.button
                onclick="window.location.href='{{ route('clients.index') }}?edit={{ $client->id }}'"
                class="border-slate-200 bg-white"
            >
                <x-icon-edit class="w-4 h-4" />
                <span>Edit Client</span>
            </x-ui.button>
        </div>
    </x-ui.page-header>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-ui.stat-card 
            label="Total Projects" 
            :value="$this->stats['project_count']" 
            icon="folder" 
            variant="primary"
        />
        <x-ui.stat-card 
            label="Active Subscriptions" 
            :value="$this->stats['active_subscriptions']" 
            icon="refresh" 
            variant="healthy"
        />
        <x-ui.stat-card 
            label="Total Billed" 
            :value="'$' . number_format($this->stats['total_billed'], 2)" 
            icon="currency-dollar" 
            variant="healthy"
        />
            <x-ui.stat-card 
                label="Pending Invoices" 
                :value="'$' . number_format($this->stats['pending_amount'], 2)" 
                icon="file-invoice" 
                variant="warning"
            />
        </div>
    
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left Column: Projects & Invoices --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- Projects Section --}}
                <x-ui.card :padding="false">
                    <x-slot:actions>
                        <x-ui.button
                            @click="$dispatch('open-modal', { id: 'project-modal' })"
                            wire:click="$dispatchTo('projects.project-form', 'open-project-modal', { clientId: {{ $client->id }} })">
                            <x-icon-plus class="w-4 h-4" />
                            <span>New Project</span>
                        </x-ui.button>
                    </x-slot:actions>
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Projects</h3>
                    </div>

                    @if($this->projects->isEmpty())
                        <div class="p-12 text-center">
                            <x-icon-folder class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                            <p class="text-slate-500">No projects found for this client.</p>
                        </div>
                    @else
                        <x-ui.data-table :headers="['Project Name', 'Subscriptions', 'Date Created', '']">
                            @foreach($this->projects as $project)
                                <tr>
                                    <td>
                                        <a href="{{ route('projects.show', $project) }}" class="group block" wire:navigate>
                                            <div class="font-bold text-primary group-hover:text-blue-600 group-hover:underline transition-colors">{{ $project->project_name }}</div>
                                            <div class="text-xs text-secondary truncate max-w-xs mt-0.5">{{ $project->description }}</div>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral badge-soft">{{ $project->subscriptions_count }} Active</span>
                                    </td>
                                    <td class="text-secondary text-sm">
                                        {{ $project->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="text-right">
                                        <x-ui.button
                                            @click="$dispatch('open-modal', { id: 'project-modal' })"
                                            wire:click="$dispatchTo('projects.project-form', 'open-project-modal', { id: {{ $project->id }} })"
                                            variant="ghost" circle title="Edit Project">
                                            <x-icon-edit class="w-4 h-4" />
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </x-ui.data-table>
                    @endif
                </x-ui.card>

                {{-- Recent Invoices Section --}}
                <x-ui.card :padding="false">
                    <x-slot:actions>
                        <x-ui.button as="a" href="{{ route('invoices.create', ['clientId' => $client->id]) }}">
                            <x-icon-plus class="w-4 h-4" />
                            <span>New Invoice</span>
                        </x-ui.button>
                    </x-slot:actions>
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Recent Invoices</h3>
                    </div>

                    @if($this->invoices->isEmpty())
                        <div class="p-12 text-center">
                            <x-icon-file-invoice class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                            <p class="text-slate-500">No invoices generated for this client.</p>
                        </div>
                @else
                    <x-ui.data-table :headers="['Invoice #', 'Amount', 'Status', 'Date', '']">
                        @foreach($this->invoices as $invoice)
                            <tr>
                                <td class="font-mono text-sm font-bold">{{ $invoice->invoice_number }}</td>
                                <td class="font-bold text-primary">${{ number_format($invoice->total_amount, 2) }}</td>
                                <td>
                                    <x-ui.badge-invoice-status :status="$invoice->status" />
                                </td>
                                <td class="text-secondary text-sm">{{ $invoice->issued_date->format('M d, Y') }}</td>
                                <td class="text-right">
                                    <x-ui.button as="a" variant="ghost" circle href="{{ route('invoices.edit', $invoice) }}">
                                        <x-icon-edit class="w-4 h-4" />
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                    <div class="p-4 border-t border-slate-100">
                        {{ $this->invoices->links() }}
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Right Column: Contact info & Quick details --}}
        <div class="space-y-8">
            <x-ui.card title="Contact Information">
                <div class="space-y-6">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Company</label>
                        <p class="text-slate-800 font-medium">{{ $client->company_name ?? 'Individual' }}</p>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Email Address</label>
                        <a href="mailto:{{ $client->email }}" class="text-accent hover:underline flex items-center gap-2">
                            <x-icon-mail class="w-4 h-4" />
                            {{ $client->email }}
                        </a>
                    </div>

                    @if($client->phone)
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Phone Number</label>
                        <a href="tel:{{ $client->phone }}" class="text-slate-800 flex items-center gap-2">
                            <x-icon-phone class="w-4 h-4" />
                            {{ $client->phone }}
                        </a>
                    </div>
                    @endif

                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Client Since</label>
                        <p class="text-slate-800">{{ $client->created_at->format('F d, Y') }}</p>
                    </div>
                </div>
            </x-ui.card>

            @if(!$this->subscriptions->isEmpty())
            <x-ui.card title="Active Subscriptions">
                <div class="space-y-4">
                    @foreach($this->subscriptions->where('status', 'Active')->take(5) as $sub)
                        <div class="p-3 bg-slate-50 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-primary">{{ $sub->service_name }}</p>
                                <p class="text-xs text-secondary">{{ $sub->project?->project_name ?? 'Unknown Project' }}</p>
                            </div>
                            <x-ui.badge-status :status="$sub->status->value" />
                        </div>
                    @endforeach
                    <x-ui.button as="a" full href="{{ route('subscriptions.index') }}">View all subscriptions</x-ui.button>
                </div>
            </x-ui.card>
            @endif
        </div>
    </div>
    <x-ui.modal id="project-modal">
        <livewire:projects.project-form :isModal="true" />
    </x-ui.modal>
</div>
