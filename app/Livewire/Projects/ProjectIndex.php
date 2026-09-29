<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Livewire\Concerns\Notifies;
use App\Models\Project;
use App\Traits\WithSorting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectIndex extends Component
{
    use Notifies, WithPagination, WithSorting;

    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public ?int $deletingId = null;

    // Trash view — a simple toggle rather than a separate page, since
    // restoring a deleted project is expected to be rare.
    public bool $showTrash = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'showTrash' => ['except' => false],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(string $ulid): void
    {
        $this->deletingId = Project::where('ulid', $ulid)->firstOrFail()->id;
        $this->dispatch('open-modal', id: 'delete-project-modal');
    }

    #[On('project-saved')]
    public function projectSaved(string $message): void
    {
        $this->notifySuccess($message);
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Project::findOrFail($this->deletingId)->delete();
            $this->notifySuccess('Project deleted successfully.');
        }
        $this->deletingId = null;
    }

    public function toggleTrash(): void
    {
        $this->showTrash = ! $this->showTrash;
        $this->resetPage();
    }

    public function restore(string $ulid): void
    {
        $project = Project::onlyTrashed()->where('ulid', $ulid)->firstOrFail();
        $project->restore();
        $this->notifySuccess("{$project->project_name} has been restored.");
    }

    #[Layout('layouts.app')]
    public function render()
    {
        // A project cascade-deleted along with its client has a trashed
        // client too — eager-load it withTrashed() so its name still shows
        // here, rather than resolving null via the default (non-trashed) scope.
        $query = Project::with(['client' => fn ($q) => $q->withTrashed()])
            ->where(function ($query) {
                $query->where('project_name', 'like', "%{$this->search}%")
                    ->orWhereHas('client', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    });
            });

        if ($this->showTrash) {
            $query->onlyTrashed();
        } else {
            // Only show projects with an active (non-deleted) client — a
            // project whose client was itself deleted is cascade-trashed
            // right along with it, so this only ever excludes the rare case
            // of a client deleted through some other path than this app.
            $query->whereHas('client');
        }

        return view('livewire.projects.project-index', [
            'projects' => $this->applySorting($query)->paginate(15),
        ]);
    }
}
