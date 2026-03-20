@props(['icon' => 'inbox', 'title', 'message' => ''])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-20 text-center']) }}>
    <div class="p-4 rounded-full bg-slate-100 mb-4 text-slate-400">
        <x-icon-{{ $icon }} class="w-10 h-10" />
    </div>
    <h3 class="text-base font-semibold text-primary mb-1">{{ $title }}</h3>
    @if($message)
        <p class="text-sm text-secondary max-w-xs mx-auto">{{ $message }}</p>
    @endif
    @if(isset($slot) && $slot->isNotEmpty())
        <div class="mt-8">
            {{ $slot }}
        </div>
    @endif
</div>
