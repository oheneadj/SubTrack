@props([
    'type' => 'success',
    'message' => null,
])

@if($message)
    <div
        class="fixed bottom-4 right-4 z-[100]"
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 3000)"
        x-transition
    >
        <div class="alert alert-{{ $type }} shadow-lg rounded-xl">
            @if($type === 'success')
                <x-icon-check class="w-5 h-5" />
            @else
                <x-icon-alert-triangle class="w-5 h-5" />
            @endif
            <span>{{ $message }}</span>
        </div>
    </div>
@endif
