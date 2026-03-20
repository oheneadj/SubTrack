<div>
    <x-ui.page-header :title="$isEdit ? 'Edit Invoice' : 'Create Invoice'" subtitle="Build a project invoice and generate PDF">
        <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-sm gap-2">
            <x-icon-arrow-left class="w-4 h-4" /> Back to Invoices
        </a>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- ═══════════════════════ LEFT: Main Content ═══════════════════════ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Client & Project --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <x-icon-user class="w-4 h-4" />
                        </div>
                        <h3 class="font-semibold text-slate-800 text-sm">Client & Project</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui.form-select model="client_id" label="Client" required :live="true">
                            <option value="">Select a Client</option>
                            @foreach($this->clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->company_name }})</option>
                            @endforeach
                        </x-ui.form-select>

                        <x-ui.form-select model="project_id" label="Project" required>
                            <option value="">Select a Project</option>
                            @foreach($this->projects as $project)
                                <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                            @endforeach
                        </x-ui.form-select>
                    </div>
                </div>
            </div>

            {{-- Invoice Items --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <x-icon-list class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">Line Items</h3>
                            <p class="text-xs text-slate-400">{{ count($items) }} {{ \Illuminate\Support\Str::plural('item', count($items)) }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="addItem" class="btn btn-sm btn-primary gap-1">
                        <x-icon-plus class="w-3.5 h-3.5" /> Add Item
                    </button>
                </div>
                <div class="p-6">
                    @if(count($items) === 0)
                        <div class="text-center py-10">
                            <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                                <x-icon-file-invoice class="w-7 h-7 text-slate-400" />
                            </div>
                            <p class="text-slate-500 text-sm font-medium">No line items yet</p>
                            <p class="text-slate-400 text-xs mt-1">Click "Add Item" to start building your invoice.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto -mx-6 px-6">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs uppercase text-slate-400 tracking-wider">
                                        <th class="text-left pb-3 font-medium">Description</th>
                                        <th class="text-center pb-3 font-medium w-20">Qty</th>
                                        <th class="text-right pb-3 font-medium w-28">Unit Price</th>
                                        <th class="text-right pb-3 font-medium w-28">Total</th>
                                        <th class="w-10 pb-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($items as $index => $item)
                                        <tr class="group hover:bg-blue-50/40 transition-colors" wire:key="item-{{ $index }}">
                                            <td class="py-2 pr-3">
                                                <input type="text" wire:model.live="items.{{ $index }}.description"
                                                    class="w-full bg-transparent border border-transparent rounded-lg px-3 py-2 text-sm text-slate-700 font-medium placeholder-slate-300 hover:border-slate-200 focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                                                    placeholder="Enter service description...">
                                                @error("items.{$index}.description")
                                                    <span class="text-error text-[10px] px-3 block mt-1">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="py-2 px-1">
                                                <input type="number" wire:model.live="items.{{ $index }}.quantity"
                                                    class="w-full bg-transparent border border-transparent rounded-lg px-2 py-2 text-sm text-center text-slate-700 hover:border-slate-200 focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                                                    step="0.01" min="0.01">
                                            </td>
                                            <td class="py-2 px-1">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-medium pointer-events-none">$</span>
                                                    <input type="number" wire:model.live="items.{{ $index }}.unit_price"
                                                        class="w-full bg-transparent border border-transparent rounded-lg pl-7 pr-3 py-2 text-sm text-right text-slate-700 hover:border-slate-200 focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                                                        step="0.01" min="0">
                                                </div>
                                            </td>
                                            <td class="py-2 px-1 text-right">
                                                <span class="font-bold text-slate-800 text-sm tabular-nums">${{ number_format($item['total'], 2) }}</span>
                                            </td>
                                            <td class="py-2 pl-2 text-right">
                                                <button type="button" wire:click="removeItem({{ $index }})"
                                                    class="btn btn-ghost btn-square btn-xs text-slate-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-all"
                                                    title="Remove item">
                                                    <x-icon-trash class="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Inline totals row --}}
                        <div class="mt-4 pt-4 border-t border-dashed border-slate-200 flex justify-end">
                            <button type="button" wire:click="addItem" class="text-blue-600 hover:text-blue-700 text-xs font-medium flex items-center gap-1 mr-auto">
                                <x-icon-plus class="w-3 h-3" /> Add another item
                            </button>
                            <div class="text-right">
                                <span class="text-xs uppercase text-slate-400 tracking-wider">Items Subtotal</span>
                                <p class="font-bold text-lg text-slate-800 tabular-nums">${{ number_format($subtotal, 2) }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                            <x-icon-note class="w-4 h-4" />
                        </div>
                        <h3 class="font-semibold text-slate-800 text-sm">Notes</h3>
                    </div>
                </div>
                <div class="p-6">
                    <x-ui.form-textarea model="notes" wire:model="notes" placeholder="Add internal notes or terms that will appear on the invoice PDF..." />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════ RIGHT: Sidebar ═══════════════════════ --}}
        <div class="space-y-6">

            {{-- Invoice Settings --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center">
                            <x-icon-settings class="w-4 h-4" />
                        </div>
                        <h3 class="font-semibold text-slate-800 text-sm">Invoice Details</h3>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <x-ui.form-input model="invoice_number" wire:model="invoice_number" label="Invoice #" required />

                    <div class="grid grid-cols-1 gap-4">
                        <x-ui.form-input model="issued_date" type="date" wire:model="issued_date" label="Invoice Date" required />
                        <x-ui.form-input model="due_date" type="date" wire:model="due_date" label="Due Date" required />
                    </div>

                    <x-ui.form-select model="status" wire:model="status" label="Status">
                        <option value="Draft">Draft</option>
                        <option value="Sent">Sent</option>
                        <option value="Paid">Paid</option>
                        <option value="Overdue">Overdue</option>
                    </x-ui.form-select>
                </div>
            </div>

            {{-- Summary & Total --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <x-icon-calculator class="w-4 h-4" />
                        </div>
                        <h3 class="font-semibold text-slate-800 text-sm">Summary</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-medium text-slate-700 tabular-nums">${{ number_format($subtotal, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <label for="tax_rate" class="text-slate-500">Tax Rate</label>
                            <div class="flex items-center gap-1">
                                <input id="tax_rate" type="number" wire:model.live="tax_rate"
                                    class="input input-bordered input-sm w-20 text-right tabular-nums" step="0.01" min="0">
                                <span class="text-slate-400 text-sm">%</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Tax Amount</span>
                            <span class="font-medium text-slate-700 tabular-nums">${{ number_format($tax_amount, 2) }}</span>
                        </div>
                    </div>

                    {{-- Grand total --}}
                    <div class="mt-4 pt-4 border-t-2 border-slate-200">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-slate-800">Grand Total</span>
                            <span class="text-2xl font-extrabold text-blue-600 tabular-nums">${{ number_format($total_amount, 2) }}</span>
                        </div>
                    </div>

                    {{-- Action --}}
                    <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary w-full mt-6 gap-2">
                        <span wire:loading.remove wire:target="save">
                          
                            {{ $isEdit ? 'Update Invoice' : 'Generate Invoice' }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-2">
                            <span class="loading loading-spinner loading-xs"></span> Saving...
                        </span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
