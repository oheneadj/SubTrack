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
            <option value="Delivered">Delivered</option>
            <option value="Bounced">Bounced</option>
            <option value="Blocked">Blocked</option>
            <option value="Complained">Complained</option>
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
        <x-ui.data-table :headers="['Recipient', 'Subject', 'Status', 'Details', 'Engagement', '']">
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
                        @if($log->status->value === 'Delivered')
                            <span class="text-slate-500">{{ $log->delivered_at?->format('M d, Y H:i') }}</span>
                        @elseif($log->status->value === 'Sent')
                            <span class="text-slate-500">{{ $log->sent_at?->format('M d, Y H:i') }}</span>
                        @elseif(in_array($log->status->value, ['Bounced', 'Blocked', 'Complained', 'Failed']))
                            <span class="text-red-600 text-xs" title="{{ $log->error_message }}">{{ str($log->error_message)->limit(60) }}</span>
                        @else
                            <span class="text-slate-400">&mdash;</span>
                        @endif
                    </td>
                    <td class="text-xs text-slate-500 space-y-0.5">
                        @if($log->opened_at)
                            <div class="flex items-center gap-1"><x-icon-eye class="w-3 h-3 text-sky-500" /> Opened {{ $log->opened_at->diffForHumans() }}</div>
                        @endif
                        @if($log->clicked_at)
                            <div class="flex items-center gap-1"><x-icon-send class="w-3 h-3 text-purple-500" /> Clicked {{ $log->clicked_at->diffForHumans() }}</div>
                        @endif
                        @if(! $log->opened_at && ! $log->clicked_at)
                            <span class="text-slate-300">&mdash;</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-ui.action-menu :slotCount="2">
                            <x-ui.button variant="ghost" size="xs" wire:click="viewEvents('{{ $log->ulid }}')">
                                <x-icon-eye class="w-3.5 h-3.5" />
                                <span>Events ({{ $log->events->count() }})</span>
                            </x-ui.button>
                            @if(in_array($log->status->value, ['Failed', 'Bounced', 'Blocked']))
                                <x-ui.button variant="error" size="xs" wire:click="resend('{{ $log->ulid }}')" wire:confirm="Resend this email to {{ $log->to_email }}?">
                                    <x-icon-refresh class="w-3.5 h-3.5" />
                                    <span>Resend</span>
                                </x-ui.button>
                            @endif
                        </x-ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif

    {{-- Events Modal --}}
    @if($showEventsModal && $this->selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 p-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Delivery Events</h3>
                        <p class="label-text-alt mt-1">{{ $this->selectedLog->to_email }}</p>
                    </div>
                    <x-ui.button wire:click="closeEvents" variant="ghost" circle class="text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                        <x-icon-x class="h-5 w-5" />
                    </x-ui.button>
                </div>

                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    @forelse($this->selectedLog->events as $event)
                        <div class="border border-slate-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-slate-700 capitalize">{{ str($event->event)->replace('_', ' ') }}</span>
                                <span class="text-xs text-slate-400">{{ $event->occurred_at?->format('M d, Y H:i:s') }}</span>
                            </div>
                            <pre class="text-xs bg-slate-50 rounded-lg p-3 overflow-x-auto text-slate-600">{{ json_encode($event->payload, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-8">No delivery events reported yet for this email.</p>
                    @endforelse
                </div>

                <div class="flex justify-end border-t border-slate-100 p-6 bg-slate-50 rounded-b-2xl">
                    <x-ui.button type="button" wire:click="closeEvents" variant="ghost">Close</x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
