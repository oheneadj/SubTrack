<div class="h-full flex flex-col">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Notifications</h3>
            <p class="text-xs text-slate-500">Stay updated with system alerts.</p>
        </div>
        @if(auth()->user()->unreadNotifications->count() > 0)
            <button wire:click="markAllAsRead" class="text-xs font-semibold text-primary hover:underline">
                Mark all as read
            </button>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/50">
        @forelse($notifications as $notification)
            <div @class([
                'p-4 rounded-xl border transition-all duration-200',
                'bg-white border-slate-200 shadow-sm' => $notification->read_at,
                'bg-blue-50/50 border-blue-100 shadow-md ring-1 ring-blue-100' => !$notification->read_at,
            ])>
                <div class="flex items-start gap-3">
                    <div @class([
                        'w-8 h-8 rounded-full flex items-center justify-center shrink-0',
                        'bg-blue-100 text-blue-600' => !$notification->read_at,
                        'bg-slate-100 text-slate-400' => $notification->read_at,
                    ])>
                        <x-icon-bell class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-xs font-bold text-slate-800 truncate">
                                {{ $notification->data['title'] ?? 'System Update' }}
                            </span>
                            <span class="text-[10px] text-slate-400 whitespace-nowrap">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed mb-3">
                            {{ $notification->data['message'] ?? 'You have a new notification.' }}
                        </p>
                        
                        <div class="flex items-center justify-between">
                            @if(isset($notification->data['action_url']))
                                <a href="{{ $notification->data['action_url'] }}" 
                                   class="btn btn-xs btn-outline btn-primary">
                                    View Details
                                </a>
                            @endif

                            @if(!$notification->read_at)
                                <button wire:click="markAsRead('{{ $notification->id }}')" 
                                        class="btn btn-xs btn-ghost text-slate-400 hover:text-primary">
                                    Mark read
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <x-icon-bell class="w-8 h-8 text-slate-300" />
                </div>
                <h4 class="text-sm font-bold text-slate-800">All caught up!</h4>
                <p class="text-xs text-slate-500 max-w-[200px] mt-1">
                    You don't have any notifications at the moment.
                </p>
            </div>
        @endforelse
    </div>
</div>
