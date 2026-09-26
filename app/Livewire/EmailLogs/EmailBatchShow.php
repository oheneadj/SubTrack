<?php

declare(strict_types=1);

namespace App\Livewire\EmailLogs;

use App\Actions\DispatchClientMailAction;
use App\Enums\EmailLogStatus;
use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shows every individual email within one Direct Mailer batch, with
 * per-row status and a resend action for anything that failed.
 */
class EmailBatchShow extends Component
{
    public string $batchId;

    public string $statusFilter = '';

    public function mount(string $batchId): void
    {
        $this->batchId = $batchId;
    }

    /** @return Collection<int, EmailLog> */
    #[Computed]
    public function logs(): Collection
    {
        return EmailLog::forBatch($this->batchId)
            ->with('client')
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function failedCount(): int
    {
        return EmailLog::forBatch($this->batchId)->failed()->count();
    }

    public function resend(string $ulid, DispatchClientMailAction $dispatcher): void
    {
        $log = EmailLog::forBatch($this->batchId)->where('ulid', $ulid)->firstOrFail();

        if ($log->status !== EmailLogStatus::Failed) {
            return;
        }

        $dispatcher->resend($log);
        session()->flash('success', "Resent email to {$log->to_email}.");
    }

    public function resendAllFailed(DispatchClientMailAction $dispatcher): void
    {
        $failedLogs = EmailLog::forBatch($this->batchId)->failed()->get();

        foreach ($failedLogs as $log) {
            $dispatcher->resend($log);
        }

        session()->flash('success', "Resent {$failedLogs->count()} failed email(s).");
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.email-logs.email-batch-show');
    }
}
