<div>
    <x-ui.page-header title="App Settings" subtitle="Configure your company identity, payment info, and invoicing defaults">
        <x-ui.button wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove>Save Changes</span>
            <span wire:loading><span class="loading loading-spinner loading-xs"></span> Saving...</span>
        </x-ui.button>
    </x-ui.page-header>

    <div class="space-y-8">

        {{-- ═══════════════ SECTION 1: Business Information ═══════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Logo & App Identity --}}
            <div class="lg:col-span-1">
                <x-ui.card>
                    <h3 class="text-lg font-bold text-primary flex items-center gap-2 mb-6">
                        <x-icon-id class="w-5 h-5 text-blue-500" />
                        App Identity
                    </h3>

                    <div class="space-y-4">
                        <x-ui.form-input label="Application Name" model="appName" placeholder="e.g. SubTrack" :error="$errors->first('appName')" />

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="label p-0">
                                    <span class="label-text font-semibold text-primary text-sm">Company Logo</span>
                                </label>
                                <p class="text-[10px] text-secondary">PNG, JPG, SVG. Max 1MB.</p>
                            </div>

                            <div class="flex flex-col gap-4">
                                {{-- Preview Box --}}
                                <div class="flex items-center gap-4 p-4 border border-slate-200 rounded-xl bg-slate-50/50">
                                    @if ($logo)
                                        <img src="{{ $logo->temporaryUrl() }}" class="w-16 h-16 rounded-lg object-contain bg-white border border-slate-200 p-2 shadow-sm">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-slate-700">New Logo File</span>
                                            <span class="text-xs text-slate-500">Ready to save</span>
                                        </div>
                                    @elseif ($currentLogo)
                                        <img src="{{ Storage::url($currentLogo) }}" class="w-16 h-16 rounded-lg object-contain bg-white border border-slate-200 p-2 shadow-sm">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-slate-700">Current Saved Logo</span>
                                            <span class="text-xs text-slate-500">Active</span>
                                        </div>
                                    @else
                                        <div class="w-16 h-16 rounded-lg bg-white border border-dashed border-slate-300 flex items-center justify-center shadow-sm">
                                            <x-icon-photo class="w-6 h-6 text-slate-400" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-slate-700">No Logo</span>
                                            <span class="text-xs text-slate-500">Upload one below</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Dropzone --}}
                                <div class="w-full" wire:ignore>
                                    <div
                                        x-data
                                        x-init="
                                            FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType, FilePondPluginFileValidateSize);
                                            const pond = FilePond.create($refs.input, {
                                                allowMultiple: false,
                                                allowImagePreview: false,
                                                labelIdle: `Drag & Drop your new logo or <span class='filepond--label-action font-semibold text-blue-500'>Browse</span>`,
                                                acceptedFileTypes: ['image/png', 'image/jpeg','image/webp', 'image/svg+xml'],
                                                maxFileSize: '1MB',
                                                server: {
                                                    process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                        @this.upload('logo', file, load, error, progress)
                                                    },
                                                    revert: (filename, load) => {
                                                        @this.removeUpload('logo', filename, load)
                                                    },
                                                },
                                            });
                                        "
                                    >
                                        <input type="file" x-ref="input">
                                    </div>
                                </div>
                            </div>
                            @error('logo') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </x-ui.card>
            </div>

            {{-- Business Details --}}
            <div class="lg:col-span-2">
                <x-ui.card title="Business Details">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui.form-input label="Company Name" model="companyName" placeholder="e.g. Acme Web Solutions" :error="$errors->first('companyName')" />
                        <x-ui.form-input label="Contact Email" model="contactEmail" type="email" placeholder="billing@acme.com" :error="$errors->first('contactEmail')" />
                        <x-ui.form-input label="Phone Number" model="businessPhone" type="tel" placeholder="+1 555-123-4567" :error="$errors->first('businessPhone')" />
                        <x-ui.form-input label="Website" model="businessWebsite" type="url" placeholder="https://acme.com" :error="$errors->first('businessWebsite')" />
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- ═══════════════ SECTION 2: Payment Details ═══════════════ --}}
        <x-ui.card>
            <h3 class="text-lg font-bold text-primary flex items-center gap-2 mb-2">
                <x-icon-credit-card class="w-5 h-5 text-blue-500" />
                Payment Details
            </h3>
            <p class="text-sm text-secondary mb-6">Bank and payment credentials that appear on your invoices.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.form-input label="Bank Name" model="bankName" placeholder="e.g. First National Bank" :error="$errors->first('bankName')" />
                <x-ui.form-input label="Account Name" model="bankAccountName" placeholder="e.g. Acme Web Solutions Ltd" :error="$errors->first('bankAccountName')" />
                <x-ui.form-input label="Account Number" model="bankAccountNumber" placeholder="e.g. 1234567890" :error="$errors->first('bankAccountNumber')" />
            </div>

            <div class="divider my-6"></div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.form-input label="PayPal Email" model="paypalEmail" type="email" placeholder="paypal@acme.com" :error="$errors->first('paypalEmail')" />
            </div>
        </x-ui.card>

        {{-- ═══════════════ SECTION 3: Invoicing Defaults ═══════════════ --}}
        <x-ui.card>
            <h3 class="text-lg font-bold text-primary flex items-center gap-2 mb-2">
                <x-icon-file-invoice class="w-5 h-5 text-blue-500" />
                Invoicing Defaults
            </h3>
            <p class="text-sm text-secondary mb-6">Default values for new invoices and the sender identity shown on PDFs.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.form-input label="Invoice Prefix" model="invoicePrefix" placeholder="INV" :error="$errors->first('invoicePrefix')" />
                <x-ui.form-input label="Default Due Days" model="invoiceDueDays" type="number" suffix="Days" :error="$errors->first('invoiceDueDays')" />
                <x-ui.form-input label="Receipt Prefix" model="receiptPrefix" placeholder="RCT" :error="$errors->first('receiptPrefix')" />
                <x-ui.form-input label="Sender Name" model="senderName" placeholder="e.g. John Smith" :error="$errors->first('senderName')" />
                <x-ui.form-input label="Sender Title" model="senderTitle" placeholder="e.g. Account Manager" :error="$errors->first('senderTitle')" />

                <div class="md:col-span-2">
                    <x-ui.form-textarea label="Invoice Footer Notes" model="invoiceFooter" rows="3" placeholder="e.g. Thank you for your business! Payment is due within the stated period." :error="$errors->first('invoiceFooter')" />
                </div>
            </div>
        </x-ui.card>

        {{-- ═══════════════ SECTION 4: Notifications ═══════════════ --}}
        <x-ui.card>
            <h3 class="text-lg font-bold text-primary flex items-center gap-2 mb-2">
                <x-icon-bell class="w-5 h-5 text-blue-500" />
                Notification Preferences
            </h3>
            <p class="text-sm text-secondary mb-6">Configure when you receive renewal reminders.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-ui.form-input label="Reminder Days" model="reminderDays" placeholder="30,14,7" :error="$errors->first('reminderDays')" />
                    <p class="text-xs text-secondary mt-2">Comma-separated list of days before expiry to send reminders. Example: <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">30,14,7</code></p>
                    <p class="text-xs text-secondary mt-1">Each subscription only uses the values that give it meaningful lead time — e.g. a monthly subscription won't get a 14 or 30-day reminder, since those land past the halfway point of its own cycle.</p>
                </div>
            </div>

            <div class="divider"></div>

            <h4 class="text-sm font-bold text-primary flex items-center gap-2 mb-2">
                <x-icon-alert-triangle class="w-4 h-4 text-amber-500" />
                Overdue Payment Policy
            </h4>
            <p class="text-sm text-secondary mb-6">Stated in reminder emails once a client misses a renewal — not automatically charged.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-ui.form-input label="Penalty Percentage" model="penaltyPercentage" type="number" step="0.01" placeholder="5" :error="$errors->first('penaltyPercentage')" />
                    <p class="text-xs text-secondary mt-2">Applied per missed renewal cycle and stated in the reminder email (e.g. 5% per missed cycle — 2 missed cycles states a 10% penalty). Never applied to the actual invoice automatically.</p>
                </div>
                <div>
                    <x-ui.form-input label="Grace Period (Days)" model="gracePeriodDays" type="number" placeholder="14" :error="$errors->first('gracePeriodDays')" />
                    <p class="text-xs text-secondary mt-2">Days after expiry before the subscription is automatically cancelled. Stated in the reminder email as the payment deadline.</p>
                </div>
            </div>

            <div class="divider"></div>

            <h4 class="text-sm font-bold text-primary flex items-center gap-2 mb-2">
                <x-icon-edit class="w-4 h-4 text-amber-500" />
                Manual Payment Corrections
            </h4>
            <p class="text-sm text-secondary mb-6">Controls for fixing a mistakenly-recorded manual payment (cash, bank transfer, etc.) from the Receipts page.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-ui.form-input label="Edit Window (Hours)" model="paymentEditWindowHours" type="number" placeholder="24" :error="$errors->first('paymentEditWindowHours')" />
                    <p class="text-xs text-secondary mt-2">How long after recording a manual payment its amount can still be corrected in place. Past this window (or once a receipt has been issued for it), it can only be voided and re-recorded.</p>
                </div>
                <div>
                    <label class="flex items-center gap-3 cursor-pointer mt-1">
                        <input type="checkbox" wire:model="requireVoidReason" class="checkbox checkbox-primary" />
                        <span class="text-sm font-semibold text-primary">Require a reason to void a payment</span>
                    </label>
                    <p class="text-xs text-secondary mt-2">When on, an admin must explain why before a manual payment can be voided — useful for audit-compliance-minded teams.</p>
                </div>
            </div>
        </x-ui.card>

        {{-- Bottom Save Bar --}}
        <div class="flex justify-end pt-2 pb-4">
            <x-ui.button size="md" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    Save All Settings
                </span>
                <span wire:loading>
                    <span class="loading loading-spinner loading-xs"></span> Saving...
                </span>
            </x-ui.button>
        </div>
    </div>
</div>

@push('styles')
<link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet" />
<link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet" />
<style>
    .filepond--root { margin-bottom: 0; font-family: inherit; }
    .filepond--panel-root { background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; }
    .filepond--drop-label { color: #64748b; font-size: 14px; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
<script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
<script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
<script src="https://unpkg.com/filepond/dist/filepond.js"></script>
@endpush
