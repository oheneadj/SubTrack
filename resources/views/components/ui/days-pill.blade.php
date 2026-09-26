@props(['days', 'missedPayments' => null])
@php
  $classes = $days <= 7
    ? 'bg-red-50 text-red-600 border-red-100'
    : 'bg-amber-50 text-amber-600 border-amber-100';
  $label = match (true) {
    $days >= 0 => $days === 0 ? 'Today' : ($days === 1 ? '1 day' : "{$days} days"),
    $missedPayments !== null => $missedPayments === 1 ? '1 payment missed' : "{$missedPayments} payments missed",
    default => abs($days).'d overdue',
  };
@endphp
<span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-full border {{ $classes }}">
    {{ $label }}
</span>
