<div>
    <x-ui.page-header title="Clients" subtitle="Manage your client relationships and contact details">
        <button wire:click="openCreate" class="btn btn-primary btn-sm flex items-center gap-2 whitespace-nowrap">
            <x-icon-plus class="w-4 h-4" />
            <span>Add Client</span>
        </button>
    </x-ui.page-header>


    {{-- Filters --}}
    <div class="mb-6 bg-white p-4 rounded-xl border border-slate-200 flex items-center gap-4">
        <div class="w-full max-w-sm">
            <x-ui.form-input 
                label="" 
                model="search" 
                placeholder="Search clients..." 
                prefix="search"
                class="input-sm"
            >
                <x-slot name="prefix">
                    <x-icon-search class="w-4 h-4 text-slate-400" />
                </x-slot>
            </x-ui.form-input>
        </div>
    </div>

    {{-- Table --}}
    @if($clients->isEmpty())
        <x-ui.empty-state 
            icon="users" 
            title="No clients found" 
            message="{{ $search ? 'Try adjusting your search query.' : 'Get started by adding your first client.' }}"
        >
            <button wire:click="openCreate" class="btn btn-primary btn-sm">Add Client</button>
        </x-ui.empty-state>
    @else
        <x-ui.data-table :headers="['name' => 'Client Name', 'email' => 'Email', 'projects_count' => 'Projects', 'created_at' => 'Registered', '']" :sortColumn="$sortColumn" :sortDirection="$sortDirection">
            @foreach($clients as $client)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td>
                        <a href="{{ route('clients.show', $client) }}" class="group block" wire:navigate>
                            <div class="font-bold text-primary group-hover:text-blue-600 group-hover:underline transition-colors">{{ $client->name }}</div>
                            <div class="text-xs text-secondary">{{ $client->company_name ?? 'Individual' }}</div>
                        </a>
                    </td>
                    <td>
                        <a href="mailto:{{ $client->email }}" class="text-accent hover:underline flex items-center gap-1.5">
                            <x-icon-mail class="w-3.5 h-3.5" />
                            {{ $client->email }}
                        </a>
                    </td>
                    <td>
                        <span class="badge badge-neutral badge-soft font-mono">{{ $client->projects_count }}</span>
                    </td>
                    <td class="text-secondary text-sm">
                        {{ $client->created_at->format('M d, Y') }}
                    </td>
                    <td class="text-right">
                        <x-ui.action-menu 
                            :viewAction="route('clients.show', $client)"
                            editAction="edit({{ $client->id }})" 
                            deleteAction="openDeleteModal({{ $client->id }})" 
                        >
                            <a href="{{ route('mail-mailer.index', ['clientId' => $client->id]) }}" class="flex items-center gap-2 btn btn-primary btn-xs gap-1.5 h-8 px-3 rounded-lg hover:bg-blue-100 transition-colors border-blue-100 text-blue-700" wire:navigate>
                                <x-icon-mail class="w-3.5 h-3.5 text-white" />
                                <span class="font-bold uppercase tracking-tight text-[10px]">Email</span>
                            </a>
                        </x-ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-6">
            {{ $clients->links() }}
        </div>
    @endif

    {{-- Delete Confirmation Modal (Password Required) --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-data @keydown.escape.window="$wire.set('showDeleteModal', false)">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-sm p-6 mx-4" @click.away="$wire.set('showDeleteModal', false)">
            <div class="text-center mb-5">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-red-100 flex items-center justify-center">
                    <x-icon-trash class="w-6 h-6 text-red-600" />
                </div>
                <h3 class="text-lg font-bold text-primary">Delete Client</h3>
                <p class="text-sm text-secondary mt-1">This will permanently remove the client. All associated projects and subscriptions will be disconnected.</p>
            </div>

            <form wire:submit="deleteWithPassword" class="space-y-4">
                <x-ui.form-input label="Enter your password to confirm" model="deletePassword" type="password" placeholder="Your password" :error="$errors->first('deletePassword')" />

                <div class="flex gap-3 pt-1">
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="btn btn-ghost btn-sm flex-1">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-error btn-sm flex-1">
                        <span wire:loading.remove wire:target="deleteWithPassword">Delete</span>
                        <span wire:loading wire:target="deleteWithPassword"><span class="loading loading-spinner loading-xs"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Client Form Modal --}}
    <div 
        x-data="{ open: @entangle('showModal') }"
        x-show="open"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen p-4">
            {{-- Overlay/Backdrop --}}
            <div 
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
                @click="open = false"
            ></div>

            {{-- Modal Content --}}
            <div 
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-2xl shadow-2xl transform transition-all max-w-2xl w-full overflow-hidden"
            >
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">
                        {{ $editingId ? 'Edit Client' : 'Add New Client' }}
                    </h3>
                    <button @click="open = false" class="btn btn-sm btn-ghost btn-circle text-slate-400">
                        <x-icon-square-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Identity & Contact</h4>
                        </div>

                        <x-ui.form-input 
                            label="Full Name" 
                            model="name" 
                            placeholder="John Doe" 
                            :error="$errors->first('name')"
                        />
                        
                        <x-ui.form-input 
                            label="Email Address" 
                            model="email" 
                            type="email" 
                            placeholder="john@example.com" 
                            :error="$errors->first('email')"
                        />

                        <div class="md:col-span-2 mt-4">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Organization & Phone</h4>
                        </div>

                        <x-ui.form-input 
                            label="Company Name" 
                            model="company_name" 
                            placeholder="Acme Inc." 
                            :error="$errors->first('company_name')"
                        />
                        
                        <x-ui.form-input 
                            label="Phone Number" 
                            model="phone" 
                            placeholder="+1 (555) 000-0000" 
                            :error="$errors->first('phone')"
                        />
                    </div>
                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 rounded-b-xl">
                    <button @click="open = false" class="btn btn-ghost btn-sm">Cancel</button>
                    <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm min-w-[100px]">
                        <span wire:loading.remove>{{ $editingId ? 'Update Client' : 'Create Client' }}</span>
                        <span wire:loading>
                            <span class="loading loading-spinner loading-xs"></span> Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
