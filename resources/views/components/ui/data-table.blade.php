@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    @foreach($headers as $header)
                        <th class="text-xs font-semibold text-secondary uppercase tracking-wider px-4 py-3">
                            {{ $header }}
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
