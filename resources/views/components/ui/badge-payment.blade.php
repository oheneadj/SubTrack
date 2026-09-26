@props(['status'])

@php
$map = [
    'Pending'  => 'bg-slate-50 text-slate-500 border border-slate-200',
    'Invoiced' => 'bg-sky-100 text-sky-700',
    'Paid'     => 'bg-green-100 text-green-700',
    'Renewed'  => 'bg-blue-100 text-blue-700',
    'Lapsed'   => 'bg-red-100 text-red-700',
];
$statusLabel = $status instanceof \BackedEnum ? $status->value : $status;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' . ($map[$statusLabel] ?? 'bg-slate-50 text-slate-500 border border-slate-200')]) }}>
    {{ $statusLabel }}
</span>
