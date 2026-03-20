@props(['message', 'count' => 0, 'actionLabel' => 'View all', 'actionLink' => '#'])
<div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4">
    <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
    </span>
    <p class="text-sm text-red-800 flex-1 font-medium">{{ $message }}</p>
    <a href="{{ $actionLink }}" class="text-xs text-red-600 font-semibold underline flex-shrink-0 hover:no-underline" wire:navigate>
        {{ $actionLabel }} →
    </a>
</div>
