@props(['heading' => '', 'subheading' => ''])

<div class="flex flex-col md:flex-row gap-8">
    {{-- Sidebar Nav --}}
    <div class="w-full md:w-56 flex-shrink-0">
        <nav class="space-y-1">
            <a href="{{ route('profile.edit') }}" wire:navigate
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('profile.edit') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <x-icon-users class="w-4 h-4" />
                Profile
            </a>
            <a href="{{ route('security.edit') }}" wire:navigate
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('security.edit') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <x-icon-settings class="w-4 h-4" />
                Security
            </a>
        </nav>
    </div>

    {{-- Content --}}
    <div class="flex-1 max-w-2xl">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-primary">{{ $heading }}</h2>
            @if($subheading)
                <p class="text-sm text-secondary mt-1">{{ $subheading }}</p>
            @endif
        </div>

        {{ $slot }}
    </div>
</div>
