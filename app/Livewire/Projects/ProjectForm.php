<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Client;
use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectForm extends Component
{
    public ?Project $project = null;

    public ?int $client_id = null;

    public string $project_name = '';

    public string $description = '';

    public bool $isModal = false;

    public function mount(?Project $project = null, ?string $clientId = null): void
    {
        if ($project && $project->exists) {
            $this->loadProject($project);
        } elseif ($clientId) {
            $client = Client::where('ulid', $clientId)->first();
            $this->client_id = $client?->id;
        } elseif (request()->query('clientId')) {
            $client = Client::where('ulid', request()->query('clientId'))->first();
            $this->client_id = $client?->id;
        }
    }

    #[On('open-project-modal')]
    public function openProjectModal(?string $id = null, ?string $clientId = null): void
    {
        $this->resetValidation();

        if ($id) {
            $project = Project::where('ulid', $id)->firstOrFail();
            $this->loadProject($project);
        } else {
            $this->project = null;
            if ($clientId) {
                $client = Client::where('ulid', $clientId)->first();
                $this->client_id = $client?->id;
            } else {
                $this->client_id = null;
            }
            $this->project_name = '';
            $this->description = '';
        }

        $this->isModal = true;
    }

    private function loadProject(Project $project)
    {
        $this->project = $project;
        $this->client_id = $project->client_id;
        $this->project_name = $project->project_name;
        $this->description = $project->description ?? '';
    }

    protected $rules = [
        'client_id' => 'required|exists:clients,id',
        'project_name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
    ];

    public function save()
    {
        $data = $this->validate();

        if ($this->project && $this->project->exists) {
            $this->project->update($data);
            $message = 'Project updated successfully.';
        } else {
            Project::create($data);
            $message = 'Project created successfully.';
        }

        if ($this->isModal) {
            $this->js("window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'project-modal' } }))");
            $this->dispatch('project-saved', $message);
        } else {
            session()->flash('success', $message);

            return redirect()->route('projects.index');
        }
    }

    public function render()
    {
        $title = $this->project && $this->project->exists ? 'Edit Project' : 'Add Project';

        return view('livewire.projects.project-form', [
            'pageTitle' => $title,
            'clients' => Client::orderBy('name')->pluck('name', 'id')->toArray(),
        ]);
    }
}
