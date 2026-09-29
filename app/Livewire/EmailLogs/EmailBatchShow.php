<?php

declare(strict_types=1);

namespace App\Livewire\EmailLogs;

use App\Actions\DispatchClientMailAction;
use App\Livewire\Concerns\Notifies;
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
    use Notifies;

    public string $batchId;

    public string $statusFilter = '';

    public bool $showEventsModal = false;

    public ?string $selectedLogUlid = null;

    public function mount(string $batchId): void
    {
        $this->batchId = $batchId;
    }

    #[Computed]
    public function selectedLog(): ?EmailLog
    {
        return $this->selectedLogUlid
            ? EmailLog::with('events')->where('ulid', $this->selectedLogUlid)->first()
            : null;
    }

    public function viewEvents(string $ulid): void
    {
        $this->selectedLogUlid = $ulid;
        $this->showEventsModal = true;
    }

    public function closeEvents(): void
    {
        $this->showEventsModal = false;
    }

    /** @return Collection<int, EmailLog> */
    #[Computed]
    public function logs(): Collection
    {
        return EmailLog::forBatch($this->batchId)
            ->with(['client', 'events'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function failedCount(): int
    {
        return EmailLog::forBatch($this->batchId)->resendable()->count();
    }

    public function resend(string $ulid, DispatchClientMailAction $dispatcher): void
    {
        $log = EmailLog::forBatch($this->batchId)->resendable()->where('ulid', $ulid)->first();

        if (! $log) {
            return;
        }

        $dispatcher->resend($log);
        $this->notifySuccess("Resent email to {$log->to_email}.");
    }

    public function resendAllFailed(DispatchClientMailAction $dispatcher): void
    {
        $logs = EmailLog::forBatch($this->batchId)->resendable()->get();

        foreach ($logs as $log) {
            $dispatcher->resend($log);
        }

        $this->notifySuccess("Resent {$logs->count()} failed email(s).");
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.email-logs.email-batch-show');
    }
}
