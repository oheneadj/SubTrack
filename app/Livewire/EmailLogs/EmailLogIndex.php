<?php

declare(strict_types=1);

namespace App\Livewire\EmailLogs;

use App\Actions\DispatchClientMailAction;
use App\Enums\EmailLogStatus;
use App\Models\EmailLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lists every Direct Mailer send as a batch — one row per batch_id, with
 * aggregate sent/failed/queued counts — so an admin can see at a glance
 * which sends had failures without opening each one.
 */
class EmailLogIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, object> */
    #[Computed]
    public function batches()
    {
        $deliveredStatuses = [EmailLogStatus::Sent->value, EmailLogStatus::Delivered->value];
        $failedStatuses = [
            EmailLogStatus::Failed->value,
            EmailLogStatus::Bounced->value,
            EmailLogStatus::Blocked->value,
            EmailLogStatus::Complained->value,
        ];
        $resendableStatuses = [
            EmailLogStatus::Failed->value,
            EmailLogStatus::Bounced->value,
            EmailLogStatus::Blocked->value,
        ];

        $query = EmailLog::query()
            ->selectRaw('batch_id, user_id, MIN(created_at) as created_at, COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN status IN ('.implode(',', array_fill(0, count($deliveredStatuses), '?')).') THEN 1 ELSE 0 END) as sent_count', $deliveredStatuses)
            ->selectRaw('SUM(CASE WHEN status IN ('.implode(',', array_fill(0, count($failedStatuses), '?')).') THEN 1 ELSE 0 END) as failed_count', $failedStatuses)
            ->selectRaw('SUM(CASE WHEN status IN ('.implode(',', array_fill(0, count($resendableStatuses), '?')).') THEN 1 ELSE 0 END) as resendable_count', $resendableStatuses)
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as queued_count', [EmailLogStatus::Queued->value])
            ->selectRaw('MIN(subject) as sample_subject')
            ->with('user')
            ->groupBy('batch_id', 'user_id')
            ->orderByDesc('created_at');

        if ($this->search !== '') {
            $matchingBatchIds = EmailLog::where('to_email', 'like', "%{$this->search}%")
                ->orWhere('to_name', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%")
                ->pluck('batch_id');

            $query->whereIn('batch_id', $matchingBatchIds);
        }

        return $query->paginate(15);
    }

    /** Resend every failed/bounced/blocked email in a batch. */
    public function resendAllFailed(string $batchId, DispatchClientMailAction $dispatcher): void
    {
        $logs = EmailLog::forBatch($batchId)->resendable()->get();

        foreach ($logs as $log) {
            $dispatcher->resend($log);
        }

        session()->flash('success', "Resent {$logs->count()} failed email(s).");
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.email-logs.email-log-index');
    }
}
