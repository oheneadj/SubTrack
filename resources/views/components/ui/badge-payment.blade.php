@props(['status'])

@php
$map = [
    'Pending'  => 'badge badge-ghost',
    'Invoiced' => 'badge badge-info badge-soft',
    'Paid'     => 'badge badge-success badge-soft',
    'Renewed'  => 'badge badge-primary badge-soft',
    'Lapsed'   => 'badge badge-error badge-soft',
];
$statusLabel = $status instanceof \BackedEnum ? $status->value : $status;
@endphp

<span {{ $attributes->merge(['class' => ($map[$statusLabel] ?? 'badge badge-ghost') . ' font-medium']) }}>
    {{ $statusLabel }}
</span>
