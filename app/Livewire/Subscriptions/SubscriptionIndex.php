<?php

namespace App\Livewire\Subscriptions;

use App\Models\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $filterService = null;
    public ?string $filterStatus = null;
    public ?int $selectedSubscriptionId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterService' => ['except' => null],
        'filterStatus' => ['except' => null],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function subscriptions()
    {
        return Subscription::query()
            ->with(['project.client', 'provider'])
            ->whereHas('project.client') // Ensure project and client are not soft-deleted
            ->when($this->search, fn($q) => $q->where(fn($sq) => 
                $sq->where('domain_name', 'like', "%{$this->search}%")
                   ->orWhereHas('provider', fn($p) => $p->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterService, fn($q) => $q->where('service_type', $this->filterService))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('expiry_date', 'asc')
            ->paginate(15);
    }

    public function confirmDelete(int $id): void
    {
        $this->selectedSubscriptionId = $id;
        $this->dispatch('open-modal', 'confirm-delete-subscription');
    }

    public function delete(): void
    {
        if ($this->selectedSubscriptionId) {
            Subscription::findOrFail($this->selectedSubscriptionId)->delete();
            $this->selectedSubscriptionId = null;
            session()->flash('success', 'Subscription deleted successfully.');
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.subscriptions.subscription-index');
    }
}
