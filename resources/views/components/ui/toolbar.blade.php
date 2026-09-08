@props([
    'searchModel' => null,
    'searchPlaceholder' => 'Search...',
])

<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row gap-4 mb-6 bg-white p-6 border border-slate-200 rounded-2xl shadow-sm']) }}>
    @if($searchModel)
        <div class="relative w-full">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <x-icon-search class="h-4 w-4 text-slate-400" />
            </div>
            <input type="text" wire:model.live.debounce.300ms="{{ $searchModel }}"
                class="input input-bordered w-full pl-10"
                placeholder="{{ $searchPlaceholder }}">
        </div>
    @endif

    {{ $slot }}
</div>
