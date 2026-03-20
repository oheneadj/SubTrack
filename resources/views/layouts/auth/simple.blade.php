<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 antialiased font-sans flex flex-col items-center justify-center p-6 md:p-10">
        <div class="w-full max-w-sm flex flex-col gap-6">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 group" wire:navigate>
                <x-app-logo class="transition-transform group-hover:scale-105" />
                <span class="sr-only">{{ config('app.name', 'SubTrack') }}</span>
            </a>
            
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm overflow-hidden relative">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
