@props(['status'])

@php
$map = [
    'Active'    => 'bg-green-100 text-green-700',
    'Expiring'  => 'bg-amber-100 text-amber-700',
    'Expired'   => 'bg-red-100 text-red-700',
    'Cancelled' => 'bg-slate-100 text-slate-600',
    'Queued'    => 'bg-sky-100 text-sky-700',
    'Sent'      => 'bg-green-100 text-green-700',
    'Failed'    => 'bg-red-100 text-red-700',
];
$statusLabel = $status instanceof \BackedEnum ? $status->value : $status;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' . ($map[$statusLabel] ?? 'bg-blue-100 text-blue-700')]) }}>
    {{ $statusLabel }}
</span>
