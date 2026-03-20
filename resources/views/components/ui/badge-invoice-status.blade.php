@props(['status'])

@php
$statusLabel = $status instanceof \BackedEnum ? $status->value : $status;

$config = match($statusLabel) {
    'Draft'   => ['class' => 'badge-neutral', 'icon' => 'edit'],
    'Sent'    => ['class' => 'badge-info badge-soft', 'icon' => 'mail'],
    'Paid'    => ['class' => 'badge-success badge-soft', 'icon' => 'check'],
    'Overdue' => ['class' => 'badge-error badge-soft', 'icon' => 'alert-circle'],
    default   => ['class' => 'badge-ghost', 'icon' => 'file-invoice'],
};
@endphp

<span {{ $attributes->merge(['class' => 'badge gap-1 font-medium px-2.5 py-3 ' . $config['class']]) }}>
    <x-dynamic-component :component="'icon-' . $config['icon']" class="w-3.5 h-3.5" />
    {{ $statusLabel }}
</span>
