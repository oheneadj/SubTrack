@props([
    'label' => null,
    'model' => null,
    'options' => [],
    'placeholder' => 'Select...',
    'error' => null,
    'live' => false
])

@php
    $modelName = $model ?? $attributes->wire('model')->value();
@endphp

<div class="flex flex-col gap-1 w-full">
    @if($label)
        <label class="text-sm font-semibold text-slate-700">{{ $label }}</label>
    @endif

    <select
        @if($live) wire:model.live="{{ $modelName }}" @else wire:model="{{ $modelName }}" @endif
        id="{{ $modelName }}"
        {{ $attributes->except(['wire:model', 'wire:model.live'])->merge(['class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' . ($error ? 'border-red-400 focus:ring-red-400 bg-red-50' : '')]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $value => $labelOption)
            <option value="{{ $value }}">{{ $labelOption }}</option>
        @endforeach

        {{ $slot }}
    </select>

    @if($modelName)
        @error($modelName)
            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
        @enderror
    @endif
</div>
