<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Livewire\Concerns\Notifies;
use App\Models\Project;
use App\Models\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectShow extends Component
{
    use Notifies;

    public Project $project;

    public ?int $deletingSubscriptionId = null;

    public function mount(Project $project)
    {
        $this->project = $project->load('client');
    }

    /** Open the shared confirm-modal for deleting a subscription listed on this project. */
    public function confirmDelete(string $ulid): void
    {
        $this->deletingSubscriptionId = Subscription::where('ulid', $ulid)->firstOrFail()->id;
        $this->dispatch('open-modal', id: 'delete-subscription-modal');
    }

    /** Delete the subscription selected via confirmDelete(). */
    public function delete(): void
    {
        if ($this->deletingSubscriptionId) {
            Subscription::findOrFail($this->deletingSubscriptionId)->delete();
            $this->notifySuccess('Subscription deleted successfully.');
        }
        $this->deletingSubscriptionId = null;
        unset($this->subscriptions, $this->stats);
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
            'total_value' => $this->project->subscriptions()->whereIn('status', ['Active', 'Expiring'])->sum('renewal_cost_usd') / 100,
        ];
    }

    #[On('project-saved')]
    public function projectSaved(string $message): void
    {
        $this->notifySuccess($message);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.projects.project-show');
    }
}
