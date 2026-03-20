@props(['viewAction' => null, 'editAction' => null, 'deleteAction' => null, 'editModalId' => null, 'align' => 'dropdown-end', 'direction' => 'dropdown-bottom', 'slotCount' => 0])

@php
    $namedActionsCount = count(array_filter([$viewAction, $editAction, $deleteAction]));
    $totalActions = $namedActionsCount + ($slotCount ?: ($slot->isNotEmpty() ? 1 : 0));
    $shouldUnfold = $totalActions < 5;
@endphp

@if($shouldUnfold)
    <div class="flex items-center justify-end gap-2">
        @if($viewAction)
            <a href="{{ $viewAction }}" class="flex items-center gap-2 btn btn-xs h-8 px-3 rounded-lg text-slate-700 transition-colors border-slate-200" wire:navigate>
                <x-icon-eye class="w-3.5 h-3.5 text-slate-500" />
                <span class="font-bold uppercase tracking-tight text-[10px]">View</span>
            </a>
        @endif

        @if($editAction)
            <button 
                @if(str_starts_with($editAction, 'window.location'))
                    onclick="{{ $editAction }}"
                @else
                    wire:click="{{ $editAction }}"
                @endif
                @if($editModalId)
                    @click="$dispatch('open-modal', { id: '{{ $editModalId }}' })"
                @endif
                class="flex items-center gap-2 btn btn-info btn-xs h-8 px-3 rounded-lg transition-colors border-blue-100 text-blue-700">
                <x-icon-edit class="w-3.5 h-3.5 text-blue-500" />
                <span class="font-bold uppercase tracking-tight text-[10px]">Edit</span>
            </button>
        @endif

        @if(isset($slot) && $slot->isNotEmpty())
            {{ $slot }}
        @endif

        @if($deleteAction)
            <button wire:click="{{ $deleteAction }}" class="flex items-center gap-2 btn btn-error btn-xs h-8 px-3 rounded-lg transition-colors border-red-100 text-red-700">
                <x-icon-trash class="w-3.5 h-3.5 text-red-500" />
                <span class="font-bold uppercase tracking-tight text-[10px]">Delete</span>
            </button>
        @endif
    </div>
@else
    <div class="dropdown {{ $align }} {{ $direction }}">
        <button type="button" tabindex="0" class="flex items-center gap-2 btn btn-square btn-xs transition-colors dropdown-toggle" aria-haspopup="menu" aria-expanded="false">
            <x-icon-dots-vertical class="w-4 h-4 text-secondary" />
        </button>
        
        <ul tabindex="0" class="dropdown-menu dropdown-open:opacity-100 hidden p-2 shadow-xl bg-white border border-slate-200 rounded-xl z-50 w-48 mt-1" role="menu">
            @if($viewAction)
                <li role="none">
                    <a href="{{ $viewAction }}" class="dropdown-item flex items-center gap-2.5 py-2 px-3  rounded-lg text-sm text-slate-700 transition-colors" role="menuitem" wire:navigate>
                        <x-icon-eye class="w-4 h-4 text-white" />
                        <span class="font-medium">View Details</span>
                    </a>
                </li>
            @endif

            @if($editAction)
                <li role="none">
                    <button 
                        @if(str_starts_with($editAction, 'window.location'))
                            onclick="{{ $editAction }}"
                        @else
                            wire:click="{{ $editAction }}"
                        @endif
                        @if($editModalId)
                            @click="$dispatch('open-modal', { id: '{{ $editModalId }}' })"
                        @endif
                        class="dropdown-item flex items-center gap-2.5 py-2 px-3 rounded-lg text-sm text-blue-700 transition-colors w-full text-left" role="menuitem">
                        <x-icon-edit class="w-4 h-4 text-white" />
                        <span class="font-medium">Edit Details</span>
                    </button>
                </li>
            @endif

            @if(isset($slot) && $slot->isNotEmpty())
                {{ $slot }}
            @endif

            @if($deleteAction)
                <div class="divider my-1"></div>
                <li role="none">
                    <button wire:click="{{ $deleteAction }}" class="dropdown-item flex items-center gap-2.5 py-2 px-3 ounded-lg text-sm text-error transition-colors w-full text-left" role="menuitem">
                        <x-icon-trash class="w-4 h-4 text-white" />
                        <span class="font-medium">Delete Item</span>
                    </button>
                </li>
            @endif
        </ul>
    </div>
@endif

