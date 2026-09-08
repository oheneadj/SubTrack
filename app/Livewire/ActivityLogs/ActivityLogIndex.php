<?php

declare(strict_types=1);

namespace App\Livewire\ActivityLogs;

use App\Models\ActivityLog;
use App\Traits\WithSorting;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogIndex extends Component
{
    use WithPagination, WithSorting;

    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public string $actionFilter = '';

    public int $perPage = 15;

    public bool $showDetailModal = false;

    public ?string $selectedLogUlid = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function viewDetails(string $ulid): void
    {
        $this->selectedLogUlid = $ulid;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedLogUlid = null;
    }

    /** The activity log currently open in the detail modal, or null if none is selected. */
    #[Computed]
    public function selectedLog(): ?ActivityLog
    {
        if (! $this->selectedLogUlid) {
            return null;
        }

        return ActivityLog::with('user')->where('ulid', $this->selectedLogUlid)->first();
    }

    public function render(): View
    {
        $logs = ActivityLog::with('user')
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%'.$this->search.'%')
                    ->orWhere('action', 'like', '%'.$this->search.'%')
                    ->orWhere('subject_type', 'like', '%'.$this->search.'%')
                    ->orWhere('ip_address', 'like', '%'.$this->search.'%');
            })
            ->when($this->actionFilter, fn ($q) => $q->where('action', $this->actionFilter));

        $logs = $this->applySorting($logs)->paginate($this->perPage);

        $actions = ActivityLog::select('action')->distinct()->pluck('action');

        return view('livewire.activity-logs.activity-log-index', [
            'logs' => $logs,
            'actions' => $actions,
        ])->layout('components.layouts.app');
    }
}
