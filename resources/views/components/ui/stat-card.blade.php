@props(['label', 'value', 'icon', 'variant' => 'neutral', 'href' => null])

@php
$variants = [
    'critical' => 'bg-gradient-to-br from-red-500 to-rose-600 text-white border-red-600 overflow-hidden',
    'warning'  => 'bg-gradient-to-br from-amber-400 to-orange-500 text-white border-amber-500 overflow-hidden',
    'healthy'  => 'bg-gradient-to-br from-emerald-400 to-green-500 text-white border-emerald-500 overflow-hidden',
    'info'     => 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white border-blue-600 overflow-hidden',
    'neutral'  => 'bg-gradient-to-br from-slate-800 to-slate-900 text-white border-slate-900 overflow-hidden',
];
$cardClass = $variants[$variant] ?? $variants['neutral'];

$baseClasses = "block rounded-2xl border p-5 relative group transition-all duration-300 hover:-translate-y-1 {$cardClass}";
@endphp

@if($href)
<a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $baseClasses]) }}>
@else
<div {{ $attributes->merge(['class' => $baseClasses]) }}>
@endif

    <!-- Decorative background glow -->
    <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
    
    <div class="relative z-10 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-2xl font-bold font-mono leading-tight tracking-tight truncate">{{ $value }}</p>
            <p class="text-xs mt-2 font-medium uppercase tracking-wider text-white/80 truncate">{{ $label }}</p>
        </div>

        <div class="p-3.5 rounded-xl bg-white/20 backdrop-blur-md border border-white/20 text-white shrink-0">
            <x-dynamic-component :component="'icon-' . $icon" class="w-6 h-6" />
        </div>
    </div>
    
    @if($href)
        <div class="relative z-10 mt-4 flex items-center text-xs font-semibold text-white/90 group-hover:text-white transition-colors">
            <span>View details</span>
            <x-icon-arrow-right class="w-4 h-4 ml-1 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" />
        </div>
    @endif

@if($href)
</a>
@else
</div>
@endif
