@props([
    'title' => null,
    'padding' => true,
])

<section {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-200 shadow-sm ' . ($padding ? 'p-6' : 'overflow-hidden')]) }}>
    @if($title || isset($actions))
        <div class="{{ $padding ? 'mb-5' : 'p-6 border-b border-slate-100' }} flex items-center justify-between">
            @if($title)
                <h3 class="text-lg font-bold text-slate-800">{{ $title }}</h3>
            @endif
            @isset($actions)
                <div class="flex items-center gap-3">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>
