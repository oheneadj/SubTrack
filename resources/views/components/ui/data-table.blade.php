@props(['headers' => [], 'sortColumn' => null, 'sortDirection' => 'asc', 'selectable' => false])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    @if($selectable)
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-sm checkbox-primary" />
                        </th>
                    @endif
                    @foreach($headers as $key => $label)
                        <th class="text-xs font-semibold text-secondary uppercase tracking-wider px-4 py-3">
                            @if(is_string($key))
                                <button type="button" wire:click="sortBy('{{ $key }}')" class="group flex items-center gap-1 hover:text-primary transition-colors focus:outline-none">
                                    {{ $label }}
                                    @if($sortColumn === $key)
                                        <span class="text-primary">
                                            @if($sortDirection === 'asc')
                                                <x-icon-arrow-up class="w-3 h-3" />
                                            @else
                                                <x-icon-arrow-down class="w-3 h-3" />
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <x-icon-arrow-up class="w-3 h-3" />
                                        </span>
                                    @endif
                                </button>
                            @else
                                {{ $label }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
