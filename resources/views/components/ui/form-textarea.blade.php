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

<div class="form-control w-full">
    @if($label)
        <label class="label">
            <span class="label-text font-semibold text-primary text-sm">{{ $label }}</span>
        </label>
    @endif

    <textarea
        wire:model="{{ $modelName }}"
        id="{{ $modelName }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'textarea textarea-bordered w-full focus:textarea-primary transition-all duration-200 ' . ($error ? 'textarea-error bg-red-50' : '')]) }}
    ></textarea>

    @if($modelName)
        @error($modelName)
            <label class="label p-1">
                <span class="label-text-alt text-error font-medium">{{ $message }}</span>
            </label>
        @enderror
    @endif
</div>
