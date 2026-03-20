<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Project;

use Livewire\Attributes\On;

class ProjectIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $confirmDelete = false;
    public ?int $deletingId = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', ['id' => 'delete-project-modal']);
    }

    #[On('project-saved')]
    public function projectSaved(string $message): void
    {
        session()->flash('success', $message);
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Project::findOrFail($this->deletingId)->delete();
            session()->flash('success', 'Project deleted successfully.');
        }
        $this->confirmDelete = false;
        $this->deletingId = null;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.projects.project-index', [
            'projects' => Project::with('client')
                ->where(function($query) {
                    $query->where('project_name', 'like', "%{$this->search}%")
                        ->orWhereHas('client', function($q) {
                            $q->where('name', 'like', "%{$this->search}%");
                        });
                })
                ->whereHas('client') // Only show projects with active (non-deleted) clients
                ->latest()
                ->paginate(15),
        ]);
    }
}
