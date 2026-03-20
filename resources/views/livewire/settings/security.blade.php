<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update Password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form wire:submit="updatePassword" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6">
                <x-ui.form-input label="Current Password" model="current_password" type="password" placeholder="Enter current password" :error="$errors->first('current_password')" />
                <x-ui.form-input label="New Password" model="password" type="password" placeholder="Enter new password" :error="$errors->first('password')" />
                <x-ui.form-input label="Confirm Password" model="password_confirmation" type="password" placeholder="Confirm new password" :error="$errors->first('password_confirmation')" />

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                        <span wire:loading.remove>Update Password</span>
                        <span wire:loading><span class="loading loading-spinner loading-xs"></span> Saving...</span>
                    </button>

                    <x-action-message class="text-sm text-green-600 font-medium" on="password-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <div class="mt-8">
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h3 class="text-lg font-bold text-primary mb-1">{{ __('Two-Factor Authentication') }}</h3>
                    <p class="text-sm text-secondary mb-6">{{ __('Add additional security to your account using two-factor authentication.') }}</p>

                    <div wire:cloak>
                        @if ($twoFactorEnabled)
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-xl">
                                    <x-icon-circle-check class="w-5 h-5 text-green-600" />
                                    <p class="text-sm text-green-700 font-medium">{{ __('Two-factor authentication is enabled.') }}</p>
                                </div>
                                <p class="text-sm text-secondary">
                                    {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from your TOTP-supported authenticator app.') }}
                                </p>
                                <button wire:click="disable" class="btn btn-error btn-sm">
                                    {{ __('Disable 2FA') }}
                                </button>
                            </div>
                        @else
                            <div class="space-y-4">
                                <p class="text-sm text-secondary">
                                    {{ __('When enabled, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                                </p>
                                <button wire:click="enable" class="btn btn-primary btn-sm">
                                    {{ __('Enable 2FA') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2FA Setup Modal --}}
            @if($showModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-data>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 mx-4">
                    <h3 class="text-lg font-bold text-primary text-center mb-2">{{ $this->modalConfig['title'] }}</h3>
                    <p class="text-sm text-secondary text-center mb-6">{{ $this->modalConfig['description'] }}</p>

                    @if ($showVerificationStep)
                        <div class="space-y-6">
                            <div class="flex justify-center">
                                <x-ui.form-input label="Verification Code" model="code" placeholder="Enter 6-digit code" :error="$errors->first('code')" />
                            </div>
                            <div class="flex gap-3">
                                <button wire:click="resetVerification" class="btn btn-ghost btn-sm flex-1">{{ __('Back') }}</button>
                                <button wire:click="confirmTwoFactor" class="btn btn-primary btn-sm flex-1" x-bind:disabled="$wire.code.length < 6">{{ __('Confirm') }}</button>
                            </div>
                        </div>
                    @else
                        @error('setupData')
                            <div class="alert alert-error mb-4 rounded-xl">{{ $message }}</div>
                        @enderror

                        @if($qrCodeSvg)
                        <div class="flex justify-center mb-6">
                            <div class="bg-white p-4 border rounded-xl border-slate-200 w-64 h-64 flex items-center justify-center">
                                {!! $qrCodeSvg !!}
                            </div>
                        </div>
                        @endif

                        @if($manualSetupKey)
                        <div class="mb-6">
                            <p class="text-xs text-secondary text-center mb-2">{{ __('Or enter this code manually:') }}</p>
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl p-3" x-data="{ copied: false }">
                                <input type="text" readonly value="{{ $manualSetupKey }}" class="flex-1 bg-transparent text-sm font-mono text-slate-800 outline-none" />
                                <button @click="navigator.clipboard.writeText('{{ $manualSetupKey }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="text-slate-500 hover:text-blue-600 transition-colors">
                                    <span x-show="!copied">📋</span>
                                    <span x-show="copied" class="text-green-500">✓</span>
                                </button>
                            </div>
                        </div>
                        @endif

                        <button wire:click="showVerificationIfNecessary" class="btn btn-primary btn-sm w-full" @disabled($errors->has('setupData'))>
                            {{ $this->modalConfig['buttonText'] }}
                        </button>
                    @endif
                </div>
            </div>
            @endif
        @endif
    </x-settings.layout>
</section>
