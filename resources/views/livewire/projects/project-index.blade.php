<div>
    <x-ui.page-header title="Projects" subtitle="{{ $showTrash ? 'Deleted projects — restore one to bring it and its subscriptions back' : 'Manage client web projects and assets' }}">
        <div class="flex items-center gap-2">
            <x-ui.button variant="ghost" wire:click="toggleTrash" class="whitespace-nowrap">
                @if($showTrash)
                    <x-icon-arrow-left class="w-4 h-4" />
                    <span>Back to Projects</span>
                @else
                    <x-icon-trash class="w-4 h-4" />
                    <span>Trash</span>
                @endif
            </x-ui.button>
            @unless($showTrash)
                <x-ui.button
                    @click="$dispatch('open-modal', { id: 'project-modal' }); Livewire.dispatchTo('projects.project-form', 'open-project-modal')"
                    class="whitespace-nowrap">
                    <x-icon-plus class="w-4 h-4" />
                    <span>Add Project</span>
                </x-ui.button>
            @endunless
        </div>
    </x-ui.page-header>

    <x-ui.toolbar searchModel="search" searchPlaceholder="Search projects or clients...">
    </x-ui.toolbar>

    {{-- Table --}}
    @if($projects->isEmpty())
        <x-ui.empty-state
            icon="{{ $showTrash ? 'trash' : 'folder' }}"
            title="{{ $showTrash ? 'Trash is empty' : 'No projects found' }}"
            message="{{ $showTrash ? 'Deleted projects will show up here.' : ($search ? 'Try adjusting your search query.' : 'Get started by adding your first project.') }}"
        >
            @unless($showTrash)
                <x-ui.button
                    @click="$dispatch('open-modal', { id: 'project-modal' }); Livewire.dispatchTo('projects.project-form', 'open-project-modal')">Add Project</x-ui.button>
            @endunless
        </x-ui.empty-state>
    @else
        <x-ui.data-table :headers="['project_name' => 'Project Name', 'Client', 'Subscriptions', 'created_at' => ($showTrash ? 'Deleted' : 'Created'), '']" :sortColumn="$sortColumn" :sortDirection="$sortDirection">
            @foreach($projects as $project)
                <tr class="hover:bg-slate-50 transition-colors" wire:key="project-{{ $project->id }}">
                    <td>
                        @if($showTrash)
                            <div class="font-bold text-slate-500">{{ $project->project_name }}</div>
                        @else
                            <a href="{{ route('projects.show', $project) }}" class="font-bold text-primary hover:text-blue-600 hover:underline transition-colors block" wire:navigate>
                                {{ $project->project_name }}
                            </a>
                        @endif
                        <div class="text-xs text-secondary truncate max-w-xs mt-0.5">{{ Str::limit($project->description, 50) }}</div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 uppercase">
                                {{ substr($project->client?->name ?? '?', 0, 2) }}
                            </div>
                            <span class="text-sm font-medium">{{ $project->client?->name ?? 'Unknown Client' }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-neutral badge-soft font-mono">{{ $project->subscriptions_count ?? 0 }}</span>
                    </td>
                    <td class="text-secondary text-sm">
                        {{ $showTrash ? $project->deleted_at->format('M d, Y') : $project->created_at->format('M d, Y') }}
                    </td>
                    <td class="text-right">
                        @if($showTrash)
                            <x-ui.button wire:click="restore('{{ $project->ulid }}')" variant="soft" size="xs">
                                <x-icon-refresh class="w-3.5 h-3.5" />
                                <span>Restore</span>
                            </x-ui.button>
                        @else
                            <x-ui.action-menu
                                editAction="Livewire.dispatchTo('projects.project-form', 'open-project-modal', { id: '{{ $project->ulid }}' })"
                                editModalId="project-modal"
                                deleteAction="confirmDelete('{{ $project->ulid }}')"
                            />
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    @endif

    {{-- Modals --}}
    <x-ui.confirm-modal 
        id="delete-project-modal"
        title="Delete Project?" 
        message="This will remove the project along with all of its subscriptions. Invoices raised against it are kept for your records."
        confirmAction="delete"
    />

    <x-ui.modal id="project-modal">
        <livewire:projects.project-form :isModal="true" />
    </x-ui.modal>
</div>
