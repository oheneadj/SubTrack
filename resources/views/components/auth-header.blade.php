@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center gap-2 mb-2">
    <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ $title }}</h2>
    <p class="text-sm text-slate-500 font-medium px-4">{{ $description }}</p>
</div>
