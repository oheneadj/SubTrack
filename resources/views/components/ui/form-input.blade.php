@props([
    'label' => null,
    'model' => null,
    'type' => 'text',
    'placeholder' => '',
    'error' => null,
    'prefix' => null,
    'suffix' => null,
    'live' => false
])

@php
    $modelName = $model ?? $attributes->wire('model')->value();
@endphp

<div class="flex flex-col gap-1 w-full">
    @if($label)
        <label class="text-sm font-semibold text-slate-700">{{ $label }}</label>
    @endif

    <div class="flex items-center">
        @if($prefix)
            <span class="inline-flex items-center px-3 py-2 bg-slate-50 border border-r-0 border-slate-300 rounded-l-lg text-sm text-slate-500 font-medium whitespace-nowrap">
                {{ $prefix }}
            </span>
        @endif

        <input
            type="{{ $type }}"
            @if($live) wire:model.live="{{ $modelName }}" @else wire:model="{{ $modelName }}" @endif
            placeholder="{{ $placeholder }}"
            id="{{ $modelName }}"
            {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' . ($prefix ? 'rounded-l-none ' : '') . ($suffix ? 'rounded-r-none ' : '') . ($error ? 'border-red-400 focus:ring-red-400 bg-red-50 ' : '')]) }}
        />

        @if($suffix)
            <span class="inline-flex items-center px-3 py-2 bg-slate-50 border border-l-0 border-slate-300 rounded-r-lg text-sm text-slate-500 font-medium whitespace-nowrap">
                {{ $suffix }}
            </span>
        @endif
    </div>

    @if($modelName)
        @error($modelName)
            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
        @enderror
    @endif
</div>
