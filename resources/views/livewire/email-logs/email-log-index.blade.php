<div>
    <x-ui.page-header title="Email Log" subtitle="Every batch of emails sent from the Direct Mailer, with delivery status." />

    <x-ui.toolbar searchModel="search" searchPlaceholder="Search recipient or subject..." />

    @if($this->batches->isEmpty())
        <x-ui.empty-state
            icon="mail"
            title="No emails sent yet"
            message="Emails sent from the Direct Mailer will show up here as batches."
        />
    @else
        <x-ui.data-table :headers="['Sent By', 'Sample Subject', 'Recipients', 'Status', 'Date', '']">
            @foreach($this->batches as $batch)
                <tr wire:key="batch-{{ $batch->batch_id }}">
                    <td class="font-medium">{{ $batch->user?->name ?? 'System' }}</td>
                    <td class="text-secondary text-sm max-w-xs truncate">{{ $batch->sample_subject }}</td>
                    <td class="text-sm">{{ $batch->total_count }}</td>
                    <td>
                        <div class="flex items-center gap-2 flex-wrap text-xs font-semibold">
                            @if($batch->sent_count > 0)
                                <span class="text-green-700">{{ $batch->sent_count }} Sent</span>
                            @endif
                            @if($batch->queued_count > 0)
                                <span class="text-sky-700">{{ $batch->queued_count }} Queued</span>
                            @endif
                            @if($batch->failed_count > 0)
                                <span class="text-red-700">{{ $batch->failed_count }} Failed</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-secondary text-sm">{{ $batch->created_at->format('M d, Y H:i') }}</td>
                    <td class="text-right">
                        <x-ui.action-menu
                            :viewAction="route('email-logs.show', $batch->batch_id)"
                            :slotCount="$batch->resendable_count > 0 ? 1 : 0"
                        >
                            @if($batch->resendable_count > 0)
                                <x-ui.button variant="error" size="xs" wire:click="resendAllFailed('{{ $batch->batch_id }}')" wire:confirm="Resend all {{ $batch->resendable_count }} failed email(s) in this batch?">
                                    <x-icon-refresh class="w-3.5 h-3.5" />
                                    <span>Resend Failed</span>
                                </x-ui.button>
                            @endif
                        </x-ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            {{ $this->batches->links() }}
        </div>
    @endif
</div>
