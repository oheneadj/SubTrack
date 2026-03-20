@props(['href', 'label', 'icon', 'active' => false])

<a href="{{ $href }}" 
   class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 
   {{ $active ? 'bg-slate-800 text-white border-l-2 border-blue-500' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
    <div class="{{ $active ? 'text-blue-500' : 'text-slate-500 group-hover:text-slate-300' }}">
        @php
            $iconSize = 'w-5 h-5';
        @endphp
        {{-- For now using placeholders until icon package issues resolved --}}
        <x-dynamic-component :component="'icon-' . $icon" class="{{ $iconSize }}" />
    </div>
    <span>{{ $label }}</span>
</a>
