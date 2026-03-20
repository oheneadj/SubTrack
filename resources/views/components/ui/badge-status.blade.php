@props(['status'])

@php
$map = [
    'Active'    => 'badge badge-success badge-soft',
    'Expiring'  => 'badge badge-warning badge-soft',
    'Expired'   => 'badge badge-error badge-soft',
    'Cancelled' => 'badge badge-neutral badge-soft',
];
$statusLabel = $status instanceof \BackedEnum ? $status->value : $status;
@endphp

<span {{ $attributes->merge(['class' => ($map[$statusLabel] ?? 'badge badge-primary badge-soft') . ' font-medium']) }}>
    {{ $statusLabel }}
</span>
