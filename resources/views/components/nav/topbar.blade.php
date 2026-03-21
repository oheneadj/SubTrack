<header class="sticky top-0 z-30 flex h-16 w-full items-center px-6 bg-white/80 backdrop-blur-md border-b border-slate-200/60 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)]">
    <div class="flex flex-1 items-center justify-between">
        <div class="flex items-center gap-4">
            {{-- Hamburger Toggle (Mobile Only) --}}
            <button type="button" class="btn btn-text btn-circle lg:hidden text-slate-500 hover:bg-slate-100 transition-colors" data-overlay="#main-sidebar"
                aria-controls="main-sidebar" aria-label="Toggle navigation">
                <x-icon-list-details class="w-6 h-6" />
            </button>
            <h2 class="text-lg font-bold text-slate-800 tracking-tight">
                {{ $title ?? '' }}
            </h2>
        </div>

        <div class="flex items-center gap-4">
            <!-- Notifications -->
            <div class="text-slate-500 hover:text-primary transition-colors cursor-pointer mr-1">
                <livewire:nav.notification-bell />
            </div>

            <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>

            <!-- User Dropdown (Powered by Alpine.js for guaranteed interaction) -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false" type="button"
                    class="flex items-center gap-3 hover:bg-slate-50 p-1 pr-3 rounded-full border border-transparent hover:border-slate-200 transition-all focus:outline-none focus:ring-2 focus:ring-primary/20"
                    aria-haspopup="menu" :aria-expanded="open" aria-label="User menu">
                    
                    <div class="avatar">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm ring-2 ring-white">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                    
                    <div class="text-left hidden sm:flex sm:flex-col">
                        <p class="text-sm font-bold text-slate-700 leading-none">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Administrator</p>
                    </div>
                    
                    <x-icon-dots-vertical class="w-4 h-4 text-slate-400 ml-1" />
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-100 py-1 z-50 overflow-hidden"
                     style="display: none;"
                     role="menu">
                    
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                        <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>

                    <div class="p-1.5 space-y-0.5">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors">
                            <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v1.18a8 8 0 0 0-1.87 1.08l-1.04-.6a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l1.04.6a8 8 0 0 0 0 2.16l-1.04.6a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l1.04-.6a8 8 0 0 0 1.87 1.08V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-1.18a8 8 0 0 0 1.87-1.08l1.04.6a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-1.04-.6a8 8 0 0 0 0-2.16l1.04-.6a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-1.04.6a8 8 0 0 0-1.87-1.08V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                            Profile Settings
                        </a>
                        
                        <div class="h-px bg-slate-100 my-1"></div>
                        
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>