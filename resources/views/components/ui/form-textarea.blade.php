@props([
    'label' => null,
    'model' => null,
    'placeholder' => '',
    'error' => null,
    'rows' => 3
])

@php
    $modelName = $model ?? $attributes->wire('model')->value();
@endphp

<div class="flex flex-col gap-1 w-full">
    @if($label)
        <label class="text-sm font-semibold text-slate-700">{{ $label }}</label>
    @endif

    <textarea
        wire:model="{{ $modelName }}"
        id="{{ $modelName }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white transition-all resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' . ($error ? 'border-red-400 focus:ring-red-400 bg-red-50' : '')]) }}
    ></textarea>

    @if($modelName)
        @error($modelName)
            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
        @enderror
    @endif
</div>
