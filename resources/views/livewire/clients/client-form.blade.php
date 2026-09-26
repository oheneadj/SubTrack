<div>
    @if(!$isModal)
        <x-ui.page-header :title="$pageTitle" subtitle="Manage identity and contact details">
            <x-ui.button as="a" variant="ghost" href="{{ route('clients.index') }}">Cancel</x-ui.button>
            <x-ui.button wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Client</span>
                <span wire:loading><span class="loading loading-spinner loading-xs"></span> Saving...</span>
            </x-ui.button>
        </x-ui.page-header>
    @endif

    <div class="{{ $isModal ? 'p-1' : 'max-w-4xl mx-auto' }}">
        <div class="{{ $isModal ? '' : 'bg-white rounded-2xl border border-slate-200 p-8' }}">
            @if($isModal)
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-slate-800">{{ $pageTitle }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Manage identity and contact details.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Identity & Contact</h4>
                </div>

                <x-ui.form-input
                    label="Full Name"
                    model="name"
                    placeholder="John Doe"
                    :error="$errors->first('name')"
                />

                <x-ui.form-input
                    label="Email Address"
                    model="email"
                    type="email"
                    placeholder="john@example.com"
                    :error="$errors->first('email')"
                />

                <div class="md:col-span-2 mt-4">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Organization & Phone</h4>
                </div>

                <x-ui.form-input
                    label="Company Name"
                    model="company_name"
                    placeholder="Acme Inc."
                    :error="$errors->first('company_name')"
                />

                <x-ui.form-input
                    label="Phone Number"
                    model="phone"
                    placeholder="+1 (555) 000-0000"
                    :error="$errors->first('phone')"
                />
            </div>

            <div class="flex justify-end pt-8 mt-8 border-t border-slate-50 gap-3">
                @if($isModal)
                    <x-ui.button type="button" variant="ghost" @click="Livewire.dispatch('close-modal', {id: 'client-modal'})">Cancel</x-ui.button>
                @else
                    <x-ui.button as="a" variant="ghost" href="{{ route('clients.index') }}">Cancel</x-ui.button>
                @endif
                <x-ui.button wire:click="save" wire:loading.attr="disabled" class="min-w-[100px]">
                    <span wire:loading.remove>
                        {{ $client && $client->exists ? 'Update Client' : 'Create Client' }}
                    </span>
                    <span wire:loading>
                        <span class="loading loading-spinner loading-xs"></span> Saving...
                    </span>
                </x-ui.button>
            </div>
        </div>
    </div>
</div>
