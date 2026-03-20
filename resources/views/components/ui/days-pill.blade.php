@props(['days'])
@php
  $classes = $days <= 7
    ? 'bg-red-50 text-red-600 border-red-100'
    : 'bg-amber-50 text-amber-600 border-amber-100';
  $label = $days < 0
    ? abs($days) . 'd overdue'
    : ($days === 0 ? 'Today' : ($days === 1 ? '1 day' : "{$days} days"));
@endphp
<span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-full border {{ $classes }}">
    {{ $label }}
</span>
