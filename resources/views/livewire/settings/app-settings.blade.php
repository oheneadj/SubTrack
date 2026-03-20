<div>
    <x-ui.page-header title="App Settings" subtitle="Configure your company identity, payment info, and invoicing defaults">
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
            <span wire:loading.remove>Save Changes</span>
            <span wire:loading><span class="loading loading-spinner loading-xs"></span> Saving...</span>
        </button>
    </x-ui.page-header>

    @if(session('success'))
        <div class="alert alert-success mb-6 rounded-xl border-green-200">
            <x-icon-circle-check class="w-5 h-5" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="space-y-8">

        {{-- ═══════════════ SECTION 1: Business Information ═══════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Logo & App Identity --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6">
                    <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                        <x-icon-id class="w-5 h-5 text-blue-500" />
                        App Identity
                    </h3>

                    <div class="space-y-4">
                        <x-ui.form-input label="Application Name" model="appName" placeholder="e.g. SubTrack" :error="$errors->first('appName')" />

                        <div class="space-y-3">
                            <label class="label">
                                <span class="label-text font-semibold text-primary text-sm">Company Logo</span>
                            </label>

                            <div class="flex items-center gap-4">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="w-16 h-16 rounded-xl object-contain bg-slate-50 border border-slate-200 p-2">
                                @elseif ($currentLogo)
                                    <img src="{{ Storage::url($currentLogo) }}" class="w-16 h-16 rounded-xl object-contain bg-slate-50 border border-slate-200 p-2">
                                @else
                                    <div class="w-16 h-16 rounded-xl bg-slate-100 border border-dashed border-slate-300 flex items-center justify-center">
                                        <x-icon-photo class="w-6 h-6 text-slate-400" />
                                    </div>
                                @endif

                                <div class="flex-1">
                                    <input type="file" wire:model="logo" class="file-input file-input-bordered file-input-primary file-input-sm w-full" />
                                    <p class="text-[10px] text-secondary mt-1">PNG, JPG, SVG. Max 1MB.</p>
                                </div>
                            </div>
                            @error('logo') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Business Details --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h3 class="text-lg font-bold text-primary flex items-center gap-2 mb-6">
                        <x-icon-building class="w-5 h-5 text-blue-500" />
                        Business Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui.form-input label="Company Name" model="companyName" placeholder="e.g. Acme Web Solutions" :error="$errors->first('companyName')" />
                        <x-ui.form-input label="Contact Email" model="contactEmail" type="email" placeholder="billing@acme.com" :error="$errors->first('contactEmail')" />
                        <x-ui.form-input label="Phone Number" model="businessPhone" type="tel" placeholder="+1 555-123-4567" :error="$errors->first('businessPhone')" />
                        <x-ui.form-input label="Website" model="businessWebsite" type="url" placeholder="https://acme.com" :error="$errors->first('businessWebsite')" />
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ SECTION 2: Payment Details ═══════════════ --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
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
        </div>

        {{-- ═══════════════ SECTION 3: Invoicing Defaults ═══════════════ --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-primary flex items-center gap-2 mb-2">
                <x-icon-file-invoice class="w-5 h-5 text-blue-500" />
                Invoicing Defaults
            </h3>
            <p class="text-sm text-secondary mb-6">Default values for new invoices and the sender identity shown on PDFs.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.form-input label="Invoice Prefix" model="invoicePrefix" placeholder="INV" :error="$errors->first('invoicePrefix')" />
                <x-ui.form-input label="Default Due Days" model="invoiceDueDays" type="number" suffix="Days" :error="$errors->first('invoiceDueDays')" />
                <x-ui.form-input label="Sender Name" model="senderName" placeholder="e.g. John Smith" :error="$errors->first('senderName')" />
                <x-ui.form-input label="Sender Title" model="senderTitle" placeholder="e.g. Account Manager" :error="$errors->first('senderTitle')" />

                <div class="md:col-span-2">
                    <x-ui.form-textarea label="Invoice Footer Notes" model="invoiceFooter" rows="3" placeholder="e.g. Thank you for your business! Payment is due within the stated period." :error="$errors->first('invoiceFooter')" />
                </div>
            </div>
        </div>

        {{-- ═══════════════ SECTION 4: Notifications ═══════════════ --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-primary flex items-center gap-2 mb-2">
                <x-icon-bell class="w-5 h-5 text-blue-500" />
                Notification Preferences
            </h3>
            <p class="text-sm text-secondary mb-6">Configure when you receive renewal reminders.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-ui.form-input label="Reminder Days" model="reminderDays" placeholder="30,14,7" :error="$errors->first('reminderDays')" />
                    <p class="text-xs text-secondary mt-2">Comma-separated list of days before expiry to send reminders. Example: <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">30,14,7</code></p>
                </div>
            </div>
        </div>

        {{-- Bottom Save Bar --}}
        <div class="flex justify-end pt-2 pb-4">
            <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
                <span wire:loading.remove>
                    Save All Settings
                </span>
                <span wire:loading>
                    <span class="loading loading-spinner loading-xs"></span> Saving...
                </span>
            </button>
        </div>
    </div>
</div>
