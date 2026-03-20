<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class ProjectShow extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project->load('client');
    }

    #[Computed]
    public function subscriptions()
    {
        return $this->project->subscriptions()->with('provider')->latest()->paginate(15);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total_subscriptions' => $this->project->subscriptions()->count(),
            'active_subscriptions' => $this->project->subscriptions()->where('status', 'Active')->count(),
            'expiring_soon' => $this->project->subscriptions()->where('status', 'Expiring')->count(),
            'total_value' => $this->project->subscriptions()->whereIn('status', ['Active', 'Expiring'])->sum('renewal_cost_usd'),
        ];
    }

    #[On('project-saved')]
    public function projectSaved(string $message): void
    {
        session()->flash('success', $message);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.projects.project-show');
    }
}
