<div>
    @if($isPaid)
        {{-- Paid state --}}
        <div class="bg-white rounded-2xl border border-green-200 p-10 text-center">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                <x-icon-circle-check class="w-8 h-8 text-green-600" />
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mb-2">Payment Received</h1>
            <p class="text-slate-500 mb-1">Invoice <span class="font-semibold text-slate-700">{{ $invoice->invoice_number }}</span></p>
            <p class="text-slate-500">Thank you, {{ $invoice->client->name }}. Your payment of <span class="font-semibold text-slate-700">{{ $invoice->formatted_total_amount }}</span> has been recorded.</p>
        </div>

    @elseif($invoice->status->value === 'Draft')
        {{-- Inactive/cancelled state --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <x-icon-file-x class="w-8 h-8 text-slate-400" />
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mb-2">Invoice Unavailable</h1>
            <p class="text-slate-500">This invoice is not currently active. Please contact us if you think this is a mistake.</p>
        </div>

    @else
        {{-- Payment page --}}
        @if(request('payment') === 'success')
            <div class="alert alert-success mb-6">
                <x-icon-circle-check class="w-5 h-5" />
                <span>Payment submitted! Your invoice will be updated once confirmed.</span>
            </div>
        @elseif(request('payment') === 'cancelled')
            <div class="alert alert-warning mb-6">
                <x-icon-alert-circle class="w-5 h-5" />
                <span>Payment was cancelled. You can try again below.</span>
            </div>
        @endif

        {{-- Invoice summary card --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
            <div class="p-6 border-b border-slate-100">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Invoice {{ $invoice->invoice_number }}</h1>
                        <p class="text-sm text-slate-500 mt-1">Issued to <span class="font-semibold text-slate-700">{{ $invoice->client->name }}</span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Due Date</p>
                        <p class="text-sm font-semibold text-slate-700">{{ $invoice->due_date->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Line items --}}
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="border-t border-slate-100">
                            <td class="px-6 py-3 text-slate-700 font-medium">{{ $item->description }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $item->period }}</td>
                            <td class="px-6 py-3 text-right text-slate-700 font-semibold">{{ $item->formatted_total }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50">
                    @if($invoice->tax_amount > 0)
                        <tr class="border-t border-slate-100">
                            <td colspan="2" class="px-6 py-3 text-right text-sm text-slate-500">Subtotal</td>
                            <td class="px-6 py-3 text-right text-sm font-medium text-slate-700">{{ $invoice->formatted_subtotal }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="px-6 py-2 text-right text-sm text-slate-500">Tax ({{ $invoice->tax_rate }}%)</td>
                            <td class="px-6 py-2 text-right text-sm font-medium text-slate-700">{{ $invoice->formatted_tax_amount }}</td>
                        </tr>
                    @endif
                    <tr class="border-t-2 border-slate-200">
                        <td colspan="2" class="px-6 py-4 text-right font-bold text-slate-800">Total Due</td>
                        <td class="px-6 py-4 text-right font-bold text-xl text-blue-600">{{ $invoice->formatted_total_amount }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Payment method selection --}}
        @if(!empty($gateways))
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="text-base font-bold text-slate-800 mb-4">Choose a payment method</h2>
                <div class="space-y-3">
                    @foreach($gateways as $slug => $label)
                        <form method="POST" action="{{ route('invoice.checkout', ['invoice' => $invoice->ulid, 'gateway' => $slug]) }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-between px-5 py-4 rounded-xl border-2 border-slate-200 hover:border-blue-500 hover:bg-blue-50 transition-all text-left group">
                                <span class="font-semibold text-slate-700 group-hover:text-blue-700">{{ $label }}</span>
                                <x-icon-arrow-right class="w-5 h-5 text-slate-400 group-hover:text-blue-500 transition-colors" />
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-amber-200 p-6 text-center">
                <p class="text-amber-700 text-sm font-medium">No payment methods are currently available. Please contact us to arrange payment.</p>
            </div>
        @endif
    @endif
</div>
