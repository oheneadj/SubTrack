<?php

namespace App\Livewire\ActivityLogs;

use App\Models\ActivityLog;
use App\Traits\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\View\View;

class ActivityLogIndex extends Component
{
    use WithPagination, WithSorting;

    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    public string $search = '';
    public string $actionFilter = '';

    protected $queryString = [
        'search'       => ['except' => ''],
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

    public function render(): View
    {
        $logs = ActivityLog::with('user')
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%')
                    ->orWhere('action', 'like', '%' . $this->search . '%')
                    ->orWhere('subject_type', 'like', '%' . $this->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $this->search . '%');
            })
            ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter));

        $logs = $this->applySorting($logs)->paginate(25);

        $actions = ActivityLog::select('action')->distinct()->pluck('action');

        return view('livewire.activity-logs.activity-log-index', [
            'logs'    => $logs,
            'actions' => $actions,
        ])->layout('components.layouts.app');
    }
}
