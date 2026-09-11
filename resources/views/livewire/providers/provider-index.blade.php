<div>
    <x-ui.page-header title="Providers" subtitle="Manage external service providers and vendors">
        <x-ui.button wire:click="openCreate">
            <x-icon-plus class="w-4 h-4" />
            <span>Add Provider</span>
        </x-ui.button>
    </x-ui.page-header>

    @if(session('success'))
        <div class="alert alert-success mb-6 shadow-sm border-0 bg-green-50 text-green-700">
            <x-icon-check class="w-5 h-5" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <x-ui.toolbar searchModel="search" searchPlaceholder="Search providers...">
    </x-ui.toolbar>

    {{-- Main Content --}}

        @if($providers->isEmpty())
            <x-ui.empty-state
                icon="world"
                title="No providers found"
                message="{{ $search ? 'Try adjusting your search query.' : 'Add your first provider.' }}"
            >
                @if(!$search)
                    <x-ui.button wire:click="openCreate" class="mt-2">
                        <x-icon-plus class="w-4 h-4" />
                        <span>Add Provider</span>
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <x-ui.data-table :headers="['Provider Name', 'Active Subscriptions', 'Contact', '']">
                @foreach($providers as $provider)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td>
                            <a href="{{ route('providers.show', $provider) }}" class="font-bold text-primary text-base hover:text-blue-600 transition-colors" wire:navigate>
                                {{ $provider->name }}
                            </a>
                        </td>
                        <td>
                            <div class="badge badge-neutral badge-sm">{{ $provider->subscriptions_count }} Subscriptions</div>
                        </td>
                        <td>
                            <div class="text-sm font-medium text-slate-700 flex flex-col gap-1">
                                @if($provider->support_email)
                                    <a href="mailto:{{ $provider->support_email }}" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                        <x-icon-mail class="w-3.5 h-3.5 text-slate-400" /> {{ $provider->support_email }}
                                    </a>
                                @endif
                                @if($provider->website)
                                    <a href="{{ $provider->website }}" target="_blank" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                        <x-icon-world class="w-3.5 h-3.5 text-slate-400" /> {{ Str::limit($provider->website, 25) }}
                                    </a>
                                @endif
                                @if(!$provider->support_email && !$provider->website)
                                    <span class="text-slate-400 italic">No contact info</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-right">
                            <x-ui.action-menu 
                                viewAction="{{ route('providers.show', $provider) }}"
                                editAction="$wire.edit('{{ $provider->ulid }}')"
                                deleteAction="$wire.openDeleteModal('{{ $provider->ulid }}')"
                            />
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>
            
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $providers->links() }}
            </div>
        @endif

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="$wire.set('showModal', false)">
        {{-- Backdrop --}}
        <div class="fixed inset-0 z-0 bg-slate-900/50 backdrop-blur-sm" @click="$wire.set('showModal', false)"></div>

        {{-- Content --}}
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                    <div class="bg-slate-50 border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                        <h3 class="font-bold text-lg text-slate-800">{{ $editingId ? 'Edit Provider' : 'Add New Provider' }}</h3>
                        <x-ui.button variant="ghost" circle wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-700">✕</x-ui.button>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-slate-700">Provider Name*</span></label>
                            <input type="text" wire:model="name" class="input input-bordered w-full focus:input-primary transition-all @error('name') input-error @enderror" placeholder="e.g. AWS, DigitalOcean">
                            @error('name') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-slate-700">Website URL</span></label>
                            <input type="url" wire:model="website" class="input input-bordered w-full focus:input-primary transition-all @error('website') input-error @enderror" placeholder="https://example.com">
                            @error('website') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-slate-700">Support Email</span></label>
                            <input type="email" wire:model="support_email" class="input input-bordered w-full focus:input-primary transition-all @error('support_email') input-error @enderror" placeholder="support@example.com">
                            @error('support_email') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex justify-end gap-3">
                        <x-ui.button variant="ghost" wire:click="$set('showModal', false)">Cancel</x-ui.button>
                        <x-ui.button wire:click="save" wire:loading.attr="disabled">
                            <span wire:loading.remove>Save Provider</span>
                            <span wire:loading>
                                <span class="loading loading-spinner loading-xs"></span> Saving...
                            </span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="$wire.set('showDeleteModal', false)">
        {{-- Backdrop --}}
        <div class="fixed inset-0 z-0 bg-slate-900/50 backdrop-blur-sm" @click="$wire.set('showDeleteModal', false)"></div>

        {{-- Content --}}
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
                    <x-ui.button variant="ghost" circle wire:click="$set('showDeleteModal', false)" class="absolute right-4 top-4 text-slate-400">✕</x-ui.button>
                    <div class="flex flex-col items-center justify-center text-center pt-4">
                        <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                            <x-icon-alert-triangle class="w-8 h-8 text-red-600" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Delete Provider?</h3>
                        <p class="text-slate-500 mb-6 text-sm">Are you sure you want to delete this provider? This action cannot be undone.</p>
                        <div class="flex justify-center gap-3 w-full">
                            <x-ui.button variant="ghost" wire:click="$set('showDeleteModal', false)" class="flex-1">Cancel</x-ui.button>
                            <x-ui.button variant="error" wire:click="delete" class="flex-1 text-white">Delete</x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
