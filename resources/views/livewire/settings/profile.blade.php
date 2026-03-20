<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6">
                <x-ui.form-input label="Name" model="name" placeholder="Your full name" :error="$errors->first('name')" />

                <div>
                    <x-ui.form-input label="Email" model="email" type="email" placeholder="your@email.com" :error="$errors->first('email')" />

                    @if ($this->hasUnverifiedEmail)
                        <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-sm text-amber-700">
                                {{ __('Your email address is unverified.') }}
                                <button wire:click.prevent="resendVerificationNotification" class="text-amber-800 font-bold underline hover:no-underline">
                                    {{ __('Click here to re-send the verification email.') }}
                                </button>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <p class="text-sm text-green-600 font-medium mt-2">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                        <span wire:loading.remove>Save Changes</span>
                        <span wire:loading><span class="loading loading-spinner loading-xs"></span> Saving...</span>
                    </button>

                    <x-action-message class="text-sm text-green-600 font-medium" on="profile-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
