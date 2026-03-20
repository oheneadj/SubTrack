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

<div class="form-control w-full">
    @if($label)
        <label class="label">
            <span class="label-text font-semibold text-primary text-sm">{{ $label }}</span>
        </label>
    @endif

    <select
        @if($live) wire:model.live="{{ $modelName }}" @else wire:model="{{ $modelName }}" @endif
        id="{{ $modelName }}"
        {{ $attributes->except(['wire:model', 'wire:model.live'])->merge(['class' => 'select select-bordered w-full focus:select-primary transition-all duration-200 ' . ($error ? 'select-error bg-red-50' : '')]) }}
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
            <label class="label p-1">
                <span class="label-text-alt text-error font-medium">{{ $message }}</span>
            </label>
        @enderror
    @endif
</div>
