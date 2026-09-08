@props(['viewAction' => null, 'editAction' => null, 'deleteAction' => null, 'editModalId' => null, 'align' => 'right', 'slotCount' => 0])

@php
    $namedActionsCount = count(array_filter([$viewAction, $editAction, $deleteAction]));
    $totalActions = $namedActionsCount + ($slotCount ?: ($slot->isNotEmpty() ? 1 : 0));
    $shouldUnfold = $totalActions < 5;

    // editAction is usually a PHP method call meant for wire:click. But it's
    // sometimes a client-side JS expression (e.g. "$dispatchTo(...)" to tell
    // a modal's child component what to load) — that belongs in @click, never
    // wire:click, which would try (and fail) to call it as a server action.
    $editIsWindowLocation = $editAction && str_starts_with($editAction, 'window.location');
    $editIsJsExpression = $editAction && str_starts_with($editAction, '$');
@endphp

@if($shouldUnfold)
    <div class="flex items-center justify-end gap-2">
        @if($viewAction)
            <a href="{{ $viewAction }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold uppercase tracking-tight rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors" wire:navigate>
                <x-icon-eye class="w-3.5 h-3.5" />
                <span>View</span>
            </a>
        @endif

        @if($editAction)
            <button
                @if($editIsWindowLocation)
                    onclick="{{ $editAction }}"
                @elseif($editIsJsExpression)
                    @click="{{ $editAction }}@if($editModalId); $dispatch('open-modal', { id: '{{ $editModalId }}' })@endif"
                @else
                    wire:click="{{ $editAction }}"
                    @if($editModalId)
                        @click="$dispatch('open-modal', { id: '{{ $editModalId }}' })"
                    @endif
                @endif
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold uppercase tracking-tight rounded-lg bg-sky-50 border border-sky-100 text-sky-700 hover:bg-sky-100 transition-colors">
                <x-icon-edit class="w-3.5 h-3.5" />
                <span>Edit</span>
            </button>
        @endif

        @if(isset($slot) && $slot->isNotEmpty())
            {{ $slot }}
        @endif

        @if($deleteAction)
            <button wire:click="{{ $deleteAction }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold uppercase tracking-tight rounded-lg bg-red-50 border border-red-100 text-red-700 hover:bg-red-100 transition-colors">
                <x-icon-trash class="w-3.5 h-3.5" />
                <span>Delete</span>
            </button>
        @endif
    </div>
@else
    <div x-data="{ open: false }" class="relative flex justify-end">
        <button type="button" @click="open = !open" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors focus:outline-none">
            <x-icon-dots-vertical class="w-4 h-4" />
        </button>

        <div x-show="open"
             @click.outside="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute right-0 mt-8 top-0 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50"
             role="menu">

            @if($viewAction)
                <a href="{{ $viewAction }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors" role="menuitem" wire:navigate>
                    <x-icon-eye class="w-4 h-4 text-slate-400" />
                    <span class="font-medium">View Details</span>
                </a>
            @endif

            @if($editAction)
                <button
                    @if($editIsWindowLocation)
                        onclick="{{ $editAction }}"
                    @elseif($editIsJsExpression)
                        @click="{{ $editAction }}@if($editModalId); $dispatch('open-modal', { id: '{{ $editModalId }}' })@endif"
                    @else
                        wire:click="{{ $editAction }}"
                        @if($editModalId)
                            @click="$dispatch('open-modal', { id: '{{ $editModalId }}' })"
                        @endif
                    @endif
                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-sky-700 hover:bg-slate-50 transition-colors w-full text-left" role="menuitem">
                    <x-icon-edit class="w-4 h-4 text-sky-400" />
                    <span class="font-medium">Edit Details</span>
                </button>
            @endif

            @if(isset($slot) && $slot->isNotEmpty())
                {{ $slot }}
            @endif

            @if($deleteAction)
                <div class="my-1 border-t border-slate-100"></div>
                <button wire:click="{{ $deleteAction }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors w-full text-left" role="menuitem">
                    <x-icon-trash class="w-4 h-4 text-red-400" />
                    <span class="font-medium">Delete Item</span>
                </button>
            @endif
        </div>
    </div>
@endif
