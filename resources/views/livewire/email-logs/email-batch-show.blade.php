<div>
    <x-ui.page-header title="Email Batch" subtitle="Individual emails within this batch, with delivery status.">
        <x-ui.button as="a" variant="ghost" href="{{ route('email-logs.index') }}" wire:navigate>
            <x-icon-arrow-left class="w-4 h-4" />
            <span>Back to Email Log</span>
        </x-ui.button>
    </x-ui.page-header>

    <x-ui.toolbar>
        <select wire:model.live="statusFilter" class="select select-bordered shrink-0 w-full md:w-48">
            <option value="">All Statuses</option>
            <option value="Queued">Queued</option>
            <option value="Sent">Sent</option>
            <option value="Failed">Failed</option>
        </select>

        @if($this->failedCount > 0)
            <x-ui.button variant="error" size="sm" wire:click="resendAllFailed" wire:confirm="Resend all {{ $this->failedCount }} failed email(s) in this batch?">
                <x-icon-refresh class="w-4 h-4" />
                <span>Resend All Failed ({{ $this->failedCount }})</span>
            </x-ui.button>
        @endif
    </x-ui.toolbar>

    @if($this->logs->isEmpty())
        <x-ui.empty-state
            icon="mail"
            title="No emails match this filter"
            message="Try a different status filter."
        />
    @else
        <x-ui.data-table :headers="['Recipient', 'Subject', 'Status', 'Sent / Error', '']">
            @foreach($this->logs as $log)
                <tr wire:key="log-{{ $log->ulid }}">
                    <td>
                        <div class="font-bold text-slate-700">{{ $log->to_name }}</div>
                        <div class="text-xs text-slate-400">{{ $log->to_email }}</div>
                    </td>
                    <td class="text-secondary text-sm max-w-xs truncate">{{ $log->subject }}</td>
                    <td>
                        <x-ui.badge-status :status="$log->status->value" />
                    </td>
                    <td class="text-sm">
                        @if($log->status->value === 'Sent')
                            <span class="text-slate-500">{{ $log->sent_at?->format('M d, Y H:i') }}</span>
                        @elseif($log->status->value === 'Failed')
                            <span class="text-red-600 text-xs" title="{{ $log->error_message }}">{{ str($log->error_message)->limit(60) }}</span>
                        @else
                            <span class="text-slate-400">&mdash;</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($log->status->value === 'Failed')
                            <x-ui.button variant="error" size="xs" wire:click="resend('{{ $log->ulid }}')" wire:confirm="Resend this email to {{ $log->to_email }}?">
                                <x-icon-refresh class="w-3.5 h-3.5" />
                                <span>Resend</span>
                            </x-ui.button>
                        @else
                            <span class="text-slate-300">&mdash;</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif
</div>
