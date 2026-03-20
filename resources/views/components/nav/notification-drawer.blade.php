<div x-data="{ open: false }" 
     @open-notifications.window="open = true" 
     @keydown.escape.window="open = false"
     x-show="open"
     class="fixed inset-0 z-[100] overflow-hidden" 
     x-cloak>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"></div>

    {{-- Slideover Panel --}}
    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10"
         x-show="open"
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">
        <div class="w-screen max-w-sm h-full bg-white shadow-2xl border-l border-slate-200 flex flex-col">
            <livewire:nav.notification-list />
        </div>
    </div>
</div>
