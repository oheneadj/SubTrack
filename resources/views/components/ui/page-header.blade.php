@props(['title', 'subtitle' => null])

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-primary tracking-tight">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-sm text-secondary mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($slot) && $slot->isNotEmpty())
        <div class="flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
