<div>
    <x-ui.page-header title="Clients" subtitle="{{ $showTrash ? 'Deleted clients — restore one to bring it and its projects/subscriptions back' : 'Manage your client relationships and contact details' }}">
        <div class="flex items-center gap-2">
            <x-ui.button variant="ghost" wire:click="toggleTrash" class="whitespace-nowrap">
                @if($showTrash)
                    <x-icon-arrow-left class="w-4 h-4" />
                    <span>Back to Clients</span>
                @else
                    <x-icon-trash class="w-4 h-4" />
                    <span>Trash</span>
                @endif
            </x-ui.button>
            @unless($showTrash)
                <x-ui.button
                    class="whitespace-nowrap"
                    @click="$dispatch('open-modal', { id: 'client-modal' }); Livewire.dispatchTo('clients.client-form', 'open-client-modal')"
                >
                    <x-icon-plus class="w-4 h-4" />
                    <span>Add Client</span>
                </x-ui.button>
            @endunless
        </div>
    </x-ui.page-header>


    {{-- Filters --}}
    <x-ui.toolbar searchModel="search" searchPlaceholder="Search clients..." />

    {{-- Table --}}
    @if($clients->isEmpty())
        <x-ui.empty-state
            icon="{{ $showTrash ? 'trash' : 'users' }}"
            title="{{ $showTrash ? 'Trash is empty' : 'No clients found' }}"
            message="{{ $showTrash ? 'Deleted clients will show up here.' : ($search ? 'Try adjusting your search query.' : 'Get started by adding your first client.') }}"
        >
            @unless($showTrash)
                <x-ui.button @click="$dispatch('open-modal', { id: 'client-modal' }); Livewire.dispatchTo('clients.client-form', 'open-client-modal')">Add Client</x-ui.button>
            @endunless
        </x-ui.empty-state>
    @else
        <x-ui.data-table :headers="['name' => 'Client Name', 'email' => 'Email', 'projects_count' => 'Projects', 'created_at' => ($showTrash ? 'Deleted' : 'Registered'), '']" :sortColumn="$sortColumn" :sortDirection="$sortDirection">
            @foreach($clients as $client)
                <tr class="hover:bg-slate-50 transition-colors" wire:key="client-{{ $client->id }}">
                    <td>
                        @if($showTrash)
                            <div class="font-bold text-slate-500">{{ $client->name }}</div>
                            <div class="text-xs text-secondary">{{ $client->company_name ?? 'Individual' }}</div>
                        @else
                            <a href="{{ route('clients.show', $client) }}" class="group block" wire:navigate>
                                <div class="font-bold text-primary group-hover:text-blue-600 group-hover:underline transition-colors">{{ $client->name }}</div>
                                <div class="text-xs text-secondary">{{ $client->company_name ?? 'Individual' }}</div>
                            </a>
                        @endif
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
                        {{ $showTrash ? $client->deleted_at->format('M d, Y') : $client->created_at->format('M d, Y') }}
                    </td>
                    <td class="text-right">
                        @if($showTrash)
                            <x-ui.button wire:click="restore('{{ $client->ulid }}')" variant="soft" size="xs">
                                <x-icon-refresh class="w-3.5 h-3.5" />
                                <span>Restore</span>
                            </x-ui.button>
                        @else
                            <x-ui.action-menu
                                :viewAction="route('clients.show', $client)"
                                editAction="Livewire.dispatchTo('clients.client-form', 'open-client-modal', { id: '{{ $client->ulid }}' })"
                                editModalId="client-modal"
                                deleteAction="openDeleteModal('{{ $client->ulid }}')"
                            >
                                <x-ui.button as="a" variant="info" size="xs" href="{{ route('mail-mailer.index', ['clientId' => $client->ulid]) }}" wire:navigate>
                                    <x-icon-mail class="w-3.5 h-3.5" />
                                    <span>Email</span>
                                </x-ui.button>
                            </x-ui.action-menu>
                        @endif
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
                <p class="text-sm text-secondary mt-1">This will remove the client along with all of their projects and subscriptions. Invoices and past payments are kept for your records.</p>
            </div>

            <form wire:submit="deleteWithPassword" class="space-y-4">
                <x-ui.form-input label="Enter your password to confirm" model="deletePassword" type="password" placeholder="Your password" :error="$errors->first('deletePassword')" />

                <div class="flex gap-3 pt-1">
                    <x-ui.button type="button" variant="ghost" wire:click="$set('showDeleteModal', false)" class="flex-1">Cancel</x-ui.button>
                    <x-ui.button type="submit" variant="error" wire:loading.attr="disabled" class="flex-1">
                        <span wire:loading.remove wire:target="deleteWithPassword">Delete</span>
                        <span wire:loading wire:target="deleteWithPassword"><span class="loading loading-spinner loading-xs"></span></span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Client Form Modal (shared with the client detail page) --}}
    <x-ui.modal id="client-modal">
        <livewire:clients.client-form :isModal="true" />
    </x-ui.modal>
</div>
