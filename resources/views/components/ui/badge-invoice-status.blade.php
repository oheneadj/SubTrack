@props(['status'])

@php
$statusLabel = $status instanceof \BackedEnum ? $status->value : $status;

$config = match($statusLabel) {
    'Draft'   => ['class' => 'bg-slate-100 text-slate-600', 'icon' => 'edit'],
    'Sent'    => ['class' => 'bg-sky-100 text-sky-700', 'icon' => 'mail'],
    'Paid'    => ['class' => 'bg-green-100 text-green-700', 'icon' => 'check'],
    'Overdue' => ['class' => 'bg-red-100 text-red-700', 'icon' => 'alert-circle'],
    default   => ['class' => 'bg-slate-50 text-slate-500 border border-slate-200', 'icon' => 'file-invoice'],
};
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ' . $config['class']]) }}>
    <x-dynamic-component :component="'icon-' . $config['icon']" class="w-3.5 h-3.5" />
    {{ $statusLabel }}
</span>
