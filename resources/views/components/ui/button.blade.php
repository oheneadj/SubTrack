@props([
    'as' => 'button',
    'variant' => 'primary',
    'size' => 'sm',
    'circle' => false,
    'full' => false,
    'soft' => false,
])

@php
    $variantClass = match ($variant) {
        'primary' => 'btn-primary',
        'ghost' => 'btn-ghost',
        'soft' => 'btn-soft',
        'outline' => 'btn-outline',
        'error' => 'btn-error',
        'success' => 'btn-success',
        'warning' => 'btn-warning',
        'secondary' => 'btn-secondary',
        default => 'btn-primary',
    };

    $sizeClass = match ($size) {
        'xs' => 'btn-xs',
        'sm' => 'btn-sm',
        'md' => '',
        default => 'btn-sm',
    };

    $classes = trim(
        'btn '.($soft ? 'btn-soft ' : '').$variantClass.' '.$sizeClass
        .($circle ? ' btn-circle' : '')
        .($full ? ' w-full' : '')
        .' flex items-center justify-center gap-2'
    );

    $tag = $as === 'a' ? 'a' : 'button';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</{{ $tag }}>
