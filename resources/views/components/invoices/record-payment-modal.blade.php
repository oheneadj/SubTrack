{{-- Shared by any Livewire component using the RecordsManualPayments trait (Invoices list, invoice-scoped Receipts page). --}}
<x-ui.modal id="record-payment-modal" maxWidth="sm">
    <h3 class="text-lg font-bold text-slate-800 mb-4">Record Payment</h3>

    <div class="flex flex-col gap-1 w-full mb-4">
        <label class="text-sm font-semibold text-slate-700">Amount received ($)</label>
        <input type="number" step="0.01" min="0.01" wire:model="recordPaymentAmount"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
        @error('recordPaymentAmount')
            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end gap-3">
        <x-ui.button type="button" variant="ghost" x-on:click="open = false">Cancel</x-ui.button>
        <x-ui.button type="button" variant="success" wire:click="submitRecordPayment" wire:loading.attr="disabled" wire:target="submitRecordPayment">
            <span class="loading loading-spinner loading-xs" wire:loading wire:target="submitRecordPayment"></span>
            Record Payment
        </x-ui.button>
    </div>
</x-ui.modal>
