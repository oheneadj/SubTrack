<button type="button" 
    class="btn btn-text btn-circle relative hover:bg-slate-100 transition-colors" 
    @click="$dispatch('open-notifications')"
    aria-label="View notifications">
    
    <x-icon-bell class="w-5 h-5 text-slate-500" />
    
    @if($unreadCount > 0)
        <span class="absolute top-1 right-1 flex h-4 w-4">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-4 w-4 bg-blue-500 text-[10px] font-bold text-white items-center justify-center p-0">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        </span>
    @endif
</button>
