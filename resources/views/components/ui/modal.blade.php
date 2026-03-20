@props([
    'id' => 'modal-' . uniqid(),
    'maxWidth' => '2xl'
])

@php
$maxWidthClass = match ($maxWidth) {
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    default => 'sm:max-w-2xl',
};
@endphp

<div x-data="{ open: false }"
     x-on:open-modal.window="if (typeof $event.detail === 'object' && $event.detail.id === '{{ $id }}') open = true"
     x-on:close-modal.window="if (typeof $event.detail === 'object' && $event.detail.id === '{{ $id }}') open = false"
     x-on:keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50"
     role="dialog"
     aria-modal="true"
     style="display: none;">

    {{-- Backdrop overlay — separate fixed layer --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
         @click="open = false"
         aria-hidden="true"></div>

    {{-- Modal content — separate fixed layer above backdrop --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white rounded-2xl text-left shadow-2xl transform transition-all {{ $maxWidthClass }} w-full p-6 sm:p-8"
                 @click.outside="open = false">

                <button @click="open = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <x-icon-x class="w-5 h-5" />
                </button>

                {{ $slot }}
            </div>
        </div>
    </div>
</div>
