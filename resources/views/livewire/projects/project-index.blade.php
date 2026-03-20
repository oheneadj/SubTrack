<div>
    <x-ui.page-header title="Projects" subtitle="Manage client web projects and assets">
        <button 
            @click="$dispatch('open-modal', { id: 'project-modal' })" 
            wire:click="$dispatchTo('projects.project-form', 'open-project-modal')" 
            class="btn btn-primary btn-sm">
            <x-icon-plus class="w-4 h-4" /> Add Project
        </button>
    </x-ui.page-header>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-6 bg-white p-4 rounded-xl border border-slate-200 flex items-center gap-4">
        <div class="w-full max-w-sm">
            <x-ui.form-input 
                label="" 
                model="search" 
                placeholder="Search projects or clients..." 
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
    @if($projects->isEmpty())
        <x-ui.empty-state 
            icon="folder" 
            title="No projects found" 
            message="{{ $search ? 'Try adjusting your search query.' : 'Get started by adding your first project.' }}"
        >
            <button 
                @click="$dispatch('open-modal', { id: 'project-modal' })" 
                wire:click="$dispatchTo('projects.project-form', 'open-project-modal')" 
                class="btn btn-primary btn-sm">Add Project</button>
        </x-ui.empty-state>
    @else
        <x-ui.data-table :headers="['Project Name', 'Client', 'Subscriptions', 'Created', '']">
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
                            editAction="$dispatchTo('projects.project-form', 'open-project-modal', { id: {{ $project->id }} })" 
                            editModalId="project-modal"
                            deleteAction="confirmDelete({{ $project->id }})" 
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
