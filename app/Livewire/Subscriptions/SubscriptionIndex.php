<?php

namespace App\Livewire\Subscriptions;

use App\Models\Subscription;
use App\Traits\WithSorting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionIndex extends Component
{
    use WithPagination, WithSorting;

    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    public string $search = '';
    public ?string $filterService = null;
    public ?string $filterStatus = null;
    public ?int $selectedSubscriptionId = null;
    
    // Bulk Actions
    public array $selectedSubscriptions = [];
    public bool $selectAll = false;

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
        $query = Subscription::query()
            ->with(['project.client', 'provider'])
            ->whereHas('project.client') // Ensure project and client are not soft-deleted
            ->when($this->search, fn($q) => $q->where(fn($sq) => 
                $sq->where('domain_name', 'like', "%{$this->search}%")
                   ->orWhereHas('provider', fn($p) => $p->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterService, fn($q) => $q->where('service_type', $this->filterService))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus));

        return $this->applySorting($query)->paginate(15);
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

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedSubscriptions = $this->subscriptions->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedSubscriptions = [];
        }
    }

    public function applyBulkStatus(string $status): void
    {
        if (empty($this->selectedSubscriptions)) {
            return;
        }

        Subscription::whereIn('id', $this->selectedSubscriptions)->update(['status' => $status]);
        
        $this->selectedSubscriptions = [];
        $this->selectAll = false;
        
        session()->flash('success', 'Selected subscriptions updated successfully.');
    }

    public function export()
    {
        $query = Subscription::query()
            ->with(['project.client', 'provider'])
            ->when($this->search, fn($q) => $q->where('domain_name', 'like', "%{$this->search}%"))
            ->when($this->filterService, fn($q) => $q->where('service_type', $this->filterService))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus));

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=subscriptions-export-' . now()->format('Y-m-d') . '.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Client', 'Project', 'Service', 'Provider', 'Domain', 'Expiry Date', 'Renewal Cost', 'Status']);

            $query->chunk(100, function($subscriptions) use ($file) {
                foreach ($subscriptions as $sub) {
                    fputcsv($file, [
                        $sub->id,
                        $sub->project?->client?->name ?? 'N/A',
                        $sub->project?->project_name ?? 'N/A',
                        $sub->service_type->value,
                        $sub->provider?->name ?? 'N/A',
                        $sub->domain_name,
                        $sub->expiry_date->format('Y-m-d'),
                        $sub->renewal_cost_usd,
                        $sub->status->value
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.subscriptions.subscription-index');
    }
}
