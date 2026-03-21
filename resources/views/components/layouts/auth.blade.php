<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 antialiased font-sans flex flex-col items-center justify-center p-6 md:p-10 overflow-hidden relative">
        <!-- Mesh Gradient Background -->
    <div class="fixed inset-0 overflow-hidden -z-10 pointer-events-none bg-slate-50">
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-blue-400 blur-[120px] opacity-20 animate-pulse" style="animation-duration: 8s;"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-blue-500 blur-[120px] opacity-20 animate-pulse" style="animation-duration: 12s;"></div>
        <div class="absolute top-[20%] right-[10%] w-[30%] h-[30%] rounded-full bg-sky-300 blur-[100px] opacity-15"></div>
        <div class="absolute bottom-[20%] left-[10%] w-[30%] h-[30%] rounded-full bg-blue-300 blur-[100px] opacity-15"></div>
    </div>

        <div class="w-full max-w-sm flex flex-col gap-8 relative z-10">
            @php
                $brandingName = \App\Models\Setting::get('business_name') ?: \App\Models\Setting::get('app_name') ?: config('app.name', 'SubTrack');
                $logoPath = \App\Models\Setting::get('logo_path');
            @endphp
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 group transition-all duration-300" wire:navigate>
                @if($logoPath)
                    <img src="{{ Storage::url($logoPath) }}" class="w-16 h-16 rounded-2xl object-contain bg-white shadow-lg shadow-blue-100 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                @else
                    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-lg shadow-blue-200 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        {{ substr($brandingName, 0, 1) }}
                    </div>
                @endif
                <span class="text-2xl font-bold text-slate-900 tracking-tight">{{ $brandingName }}</span>
            </a>
            
            <div class="bg-white/80 backdrop-blur-xl p-8 rounded-3xl border border-white shadow-2xl shadow-slate-200/50 overflow-hidden relative">
                <!-- Subtle Top Highlight -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-blue-500/20 to-transparent"></div>
                
                {{ $slot }}
            </div>

            @if(Route::currentRouteName() === 'login')
                <p class="text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} {{ $brandingName }}. All rights reserved.
                </p>
            @endif
        </div>
    </body>
</html>
