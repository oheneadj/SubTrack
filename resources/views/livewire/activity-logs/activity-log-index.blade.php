<div>
    <x-ui.page-header title="Activity Logs" subtitle="Audit trail of all system and user actions.">
        <div class="flex gap-2">
            <select wire:model.live="actionFilter" class="select select-sm select-bordered">
                <option value="">All Actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}">{{ str($action)->replace('.', ' ')->title() }}</option>
                @endforeach
            </select>
            <div class="relative">
                <x-icon-search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search logs..." class="input input-sm input-bordered pl-9 w-64" />
            </div>
        </div>
    </x-ui.page-header>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                        <th class="py-4 px-6 font-semibold">User</th>
                        <th class="py-4 px-6 font-semibold">
                            <button type="button" wire:click="sortBy('action')" class="group flex items-center gap-1 hover:text-primary transition-colors focus:outline-none">
                                Action
                                @if($sortColumn === 'action')
                                    <span class="text-primary"><x-icon-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" /></span>
                                @else
                                    <span class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity"><x-icon-arrow-up class="w-3 h-3" /></span>
                                @endif
                            </button>
                        </th>
                        <th class="py-4 px-6 font-semibold">Description</th>
                        <th class="py-4 px-6 font-semibold">Subject</th>
                        <th class="py-4 px-6 font-semibold">
                            <button type="button" wire:click="sortBy('ip_address')" class="group flex items-center gap-1 hover:text-primary transition-colors focus:outline-none">
                                IP Address
                                @if($sortColumn === 'ip_address')
                                    <span class="text-primary"><x-icon-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" /></span>
                                @else
                                    <span class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity"><x-icon-arrow-up class="w-3 h-3" /></span>
                                @endif
                            </button>
                        </th>
                        <th class="py-4 px-6 font-semibold">
                            <button type="button" wire:click="sortBy('created_at')" class="group flex items-center gap-1 hover:text-primary transition-colors focus:outline-none">
                                Date
                                @if($sortColumn === 'created_at')
                                    <span class="text-primary"><x-icon-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" /></span>
                                @else
                                    <span class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity"><x-icon-arrow-up class="w-3 h-3" /></span>
                                @endif
                            </button>
                        </th>
                        <th class="py-4 px-6 font-semibold text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6">
                                @if($log->user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                            {{ substr($log->user->name, 0, 1) }}
                                        </div>
                                        <span class="text-xs font-medium text-slate-700">{{ $log->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">System</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="badge badge-sm {{ str_starts_with($log->action, 'model.') ? 'badge-info' : (str_starts_with($log->action, 'auth.') ? 'badge-primary' : 'badge-neutral') }}">
                                    {{ str($log->action)->replace('.', ' ')->title() }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600">
                                {{ $log->description }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 italic">
                                @if($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-4 px-6 text-[10px] font-mono text-slate-400">
                                {{ $log->ip_address }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500">
                                {{ $log->created_at->format('M d, Y H:i:s') }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($log->properties && count($log->properties) > 0)
                                    <button class="btn btn-ghost btn-xs text-blue-600" onclick="activity_log_modal_{{ $log->id }}.showModal()">
                                        <x-icon-eye class="w-3 h-3" />
                                    </button>

                                    <dialog id="activity_log_modal_{{ $log->id }}" class="modal">
                                        <div class="modal-box max-w-2xl bg-white p-0 overflow-hidden">
                                            <div class="p-6 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                                                <h3 class="font-bold text-lg text-slate-800">Log Details #{{ $log->id }}</h3>
                                                <span class="badge badge-lg badge-neutral">{{ str($log->action)->replace('.', ' ')->title() }}</span>
                                            </div>
                                            <div class="p-6">
                                                <div class="grid grid-cols-2 gap-4 mb-6">
                                                    <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                                                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">User Info</div>
                                                        <div class="text-sm font-medium text-slate-700">{{ $log->user?->name ?? 'System' }}</div>
                                                        <div class="text-[10px] text-slate-400 truncate">{{ $log->user_agent }}</div>
                                                    </div>
                                                    <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                                                        <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Context</div>
                                                        <div class="text-sm font-medium text-slate-700">{{ $log->ip_address }}</div>
                                                        <div class="text-[10px] text-slate-400">{{ $log->created_at->format('l, F j, Y — H:i:s') }}</div>
                                                    </div>
                                                </div>

                                                <div class="space-y-4">
                                                    @if(isset($log->properties['old']) && $log->properties['old'])
                                                        <div>
                                                            <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Changes</h4>
                                                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 font-mono text-[10px] space-y-2">
                                                                @foreach($log->properties['old'] as $key => $oldValue)
                                                                    <div class="flex items-start gap-4">
                                                                        <span class="text-slate-500 w-24 shrink-0">{{ $key }}:</span>
                                                                        <div class="flex flex-col gap-1">
                                                                            <span class="text-red-500 line-through">{{ var_export($oldValue, true) }}</span>
                                                                            <span class="text-green-600">{{ var_export($log->properties['attributes'][$key] ?? null, true) }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div>
                                                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Raw Data</h4>
                                                        <pre class="bg-slate-900 text-slate-300 rounded-lg p-4 text-[10px] overflow-x-auto"><code>{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</code></pre>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-action p-4 bg-slate-50 border-t border-slate-100">
                                                <form method="dialog">
                                                    <button class="btn btn-sm">Close</button>
                                                </form>
                                            </div>
                                        </div>
                                        <form method="dialog" class="modal-backdrop">
                                            <button>close</button>
                                        </form>
                                    </dialog>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6">
                                <x-ui.empty-state 
                                    icon="clipboard-list" 
                                    title="No logs found" 
                                    message="No activity has been recorded yet matching your filters." 
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
            <div class="p-6 border-t border-slate-200 bg-slate-50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
