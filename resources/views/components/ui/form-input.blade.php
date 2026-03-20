@props([
    'label' => null,
    'model' => null,
    'type' => 'text',
    'placeholder' => '',
    'error' => null,
    'prefix' => null,
    'suffix' => null
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

    <div class="flex items-center gap-0">
        @if($prefix)
            <span class="inline-flex items-center px-3 py-2 bg-slate-50 border border-r-0 border-slate-300 rounded-l-lg text-sm text-secondary font-medium whitespace-nowrap">
                {{ $prefix }}
            </span>
        @endif

        <input
            type="{{ $type }}"
            wire:model="{{ $modelName }}"
            placeholder="{{ $placeholder }}"
            id="{{ $modelName }}"
            {{ $attributes->merge(['class' => 'input input-bordered w-full focus:input-primary transition-all duration-200 ' . ($prefix ? 'rounded-l-none ' : '') . ($suffix ? 'rounded-r-none ' : '') . ($error ? 'input-error bg-red-50' : '')]) }}
        />

        @if($suffix)
            <span class="inline-flex items-center px-3 py-2 bg-slate-50 border border-l-0 border-slate-300 rounded-r-lg text-sm text-secondary font-medium whitespace-nowrap">
                {{ $suffix }}
            </span>
        @endif
    </div>

    @if($modelName)
        @error($modelName)
            <label class="label p-1">
                <span class="label-text-alt text-error font-medium">{{ $message }}</span>
            </label>
        @enderror
    @endif
</div>
