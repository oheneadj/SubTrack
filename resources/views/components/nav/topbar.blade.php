<header class="sticky top-0 z-30 flex h-16 w-full items-center px-6">
    <div class="flex flex-1 items-center justify-between">
        <div class="flex items-center gap-4">
            {{-- Hamburger Toggle (Mobile Only) --}}
            <button type="button" class="btn btn-text btn-circle lg:hidden" data-overlay="#main-sidebar"
                aria-controls="main-sidebar" aria-label="Toggle navigation">
                <x-icon-list-details class="w-6 h-6" />
            </button>
            <h2 class="text-lg font-semibold text-slate-800">
                {{ $title ?? '' }}
            </h2>
        </div>

        <div class="flex items-center gap-3">
            <!-- Notifications -->
            <livewire:nav.notification-bell />

            <!-- User Dropdown -->
            <div class="dropdown bg-blue-100 rounded-full dropdown-end">
                <button id="user-dropdown-btn" type="button"
                    class="dropdown-toggle flex items-center gap-2 hover:bg-slate-50 p-1.5 rounded-lg transition-colors"
                    aria-haspopup="menu" aria-expanded="false" aria-label="User menu">
                    <div class="avatar placeholder">
                        <div
                            class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="text-sm font-medium text-slate-700 leading-none">{{ auth()->user()->name }}</p>
                    </div>
                    <x-icon-dots-vertical class="w-4 h-4 text-slate-400" />
                </button>
                <ul class="dropdown-menu dropdown-open:opacity-100 hidden p-2 shadow-lg bg-white border border-slate-200 rounded-xl w-52 mt-2"
                    role="menu" aria-labelledby="user-dropdown-btn">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile Settings</a></li>
                    <div class="divider my-1"></div>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="dropdown-item text-error w-full text-left">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>