<x-layouts.auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;
                    this.code = '';
                    this.recovery_code = '';
                    $nextTick(() => {
                        if (this.showRecoveryInput) {
                            $refs.recovery_code.focus();
                        } else {
                            $refs.code.focus();
                        }
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('App Code')"
                    :description="__('Enter the code from your authenticator app')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Recovery Code')"
                    :description="__('Enter one of your emergency recovery codes')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-5 mt-2">
                @csrf

                <div x-show="!showRecoveryInput" class="form-control w-full">
                    <label class="label py-1">
                        <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Authentication Code') }}</span>
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                            <x-icon-shield-check class="w-4 h-4" />
                        </div>
                        <input 
                            name="code" 
                            type="text" 
                            x-ref="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            placeholder="000000"
                            class="input input-bordered w-full pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 text-center tracking-[0.5em] font-bold text-lg"
                        />
                    </div>
                </div>

                <div x-show="showRecoveryInput" class="form-control w-full">
                    <label class="label py-1">
                        <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Recovery Code') }}</span>
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                            <x-icon-key class="w-4 h-4" />
                        </div>
                        <input 
                            name="recovery_code" 
                            type="text" 
                            x-ref="recovery_code"
                            autocomplete="one-time-code"
                            placeholder="abcdef-123456"
                            class="input input-bordered w-full pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 font-mono text-center"
                        />
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-error text-xs py-2 rounded-xl border-red-100 bg-red-50 text-red-700">
                        <x-icon-alert-circle class="w-4 h-4" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-full h-11 rounded-xl shadow-lg shadow-blue-200 font-bold tracking-wide">
                        {{ __('Verify & Continue') }}
                    </button>
                </div>

                <div class="text-xs text-center text-slate-500 pt-2">
                    <button type="button" @click="toggleInput" class="text-blue-600 font-bold hover:underline">
                        <span x-show="!showRecoveryInput">{{ __('Use a recovery code') }}</span>
                        <span x-show="showRecoveryInput">{{ __('Use an authentication code') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.auth>
