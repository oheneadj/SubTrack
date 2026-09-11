<div>
    <x-ui.page-header title="Projects" subtitle="Manage client web projects and assets">
        <x-ui.button
            @click="$dispatch('open-modal', { id: 'project-modal' }); $dispatchTo('projects.project-form', 'open-project-modal')"
            class="whitespace-nowrap">
            <x-icon-plus class="w-4 h-4" />
            <span>Add Project</span>
        </x-ui.button>
    </x-ui.page-header>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <x-ui.toolbar searchModel="search" searchPlaceholder="Search projects or clients...">
    </x-ui.toolbar>

    {{-- Table --}}
    @if($projects->isEmpty())
        <x-ui.empty-state
            icon="folder"
            title="No projects found"
            message="{{ $search ? 'Try adjusting your search query.' : 'Get started by adding your first project.' }}"
        >
            <x-ui.button
                @click="$dispatch('open-modal', { id: 'project-modal' }); $dispatchTo('projects.project-form', 'open-project-modal')">Add Project</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.data-table :headers="['project_name' => 'Project Name', 'Client', 'Subscriptions', 'created_at' => 'Created', '']" :sortColumn="$sortColumn" :sortDirection="$sortDirection">
            @foreach($projects as $project)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td>
                        <a href="{{ route('projects.show', $project) }}" class="font-bold text-primary hover:text-blue-600 hover:underline transition-colors block" wire:navigate>
                            {{ $project->project_name }}
                        </a>
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
                        {{ $project->created_at->format('M d, Y') }}
                    </td>
                    <td class="text-right">
                        <x-ui.action-menu 
                            editAction="$dispatchTo('projects.project-form', 'open-project-modal', { id: '{{ $project->ulid }}' })"
                            editModalId="project-modal"
                            deleteAction="confirmDelete('{{ $project->ulid }}')"
                        />
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
        message="This will delete the project. Associated subscriptions will also be moved to trash (if soft deletes enabled)." 
        confirmAction="delete"
    />

    <x-ui.modal id="project-modal">
        <livewire:projects.project-form :isModal="true" />
    </x-ui.modal>
</div>
