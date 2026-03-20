@props(['type', 'description', 'time'])
@php
$config = match($type) {
    \App\Enums\ActivityEventType::InvoicePaid      => ['bg-green-50',  'text-green-600',  'check'],
    \App\Enums\ActivityEventType::InvoiceSent      => ['bg-blue-50',   'text-blue-600',   'file-invoice'],
    \App\Enums\ActivityEventType::InvoiceCreated   => ['bg-blue-50',   'text-blue-600',   'plus'],
    \App\Enums\ActivityEventType::InvoiceOverdue   => ['bg-red-50',    'text-red-600',    'alert-circle'],
    \App\Enums\ActivityEventType::ReminderSent     => ['bg-amber-50',  'text-amber-600',  'bell'],
    \App\Enums\ActivityEventType::ClientCreated    => ['bg-purple-50', 'text-purple-600', 'users'],
    \App\Enums\ActivityEventType::RenewalConfirmed => ['bg-teal-50',   'text-teal-600',   'refresh'],
    \App\Enums\ActivityEventType::SubscriptionExpiring => ['bg-orange-50', 'text-orange-600', 'clock'],
    \App\Enums\ActivityEventType::SubscriptionExpired  => ['bg-red-50',    'text-red-600',    'alert-triangle'],
    \App\Enums\ActivityEventType::SubscriptionCreated  => ['bg-green-50',  'text-green-600',  'plus'],
    default                                        => ['bg-slate-50',  'text-slate-500',  'list-details'],
};
@endphp
<div class="flex items-start gap-3 py-2.5 border-b border-slate-100 last:border-0">
    <div class="p-1.5 rounded-lg flex-shrink-0 {{ $config[0] }}">
        <x-dynamic-component :component="'icon-' . $config[2]" class="w-3.5 h-3.5 {{ $config[1] }}" />
    </div>
    <div class="min-w-0">
        <p class="text-xs text-slate-700 leading-snug">{!! $description !!}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">{{ $time }}</p>
    </div>
</div>
