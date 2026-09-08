<x-ui.card
    class="space-y-6"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="space-y-2">
        <div class="flex items-center gap-2">
            <x-icon-lock class="w-4 h-4" />
            <h3 class="text-lg font-semibold">{{ __('2FA recovery codes') }}</h3>
        </div>
        <p class="text-sm text-base-content/60">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </p>
    </div>

    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.button
                x-show="!showRecoveryCodes"
                @click="showRecoveryCodes = true"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
            >
                <x-icon-eye class="w-4 h-4" />
                {{ __('View recovery codes') }}
            </x-ui.button>

            <x-ui.button
                x-show="showRecoveryCodes"
                @click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
            >
                <x-icon-eye class="w-4 h-4" />
                {{ __('Hide recovery codes') }}
            </x-ui.button>

            @if (filled($recoveryCodes))
                <x-ui.button
                    variant="secondary"
                    x-show="showRecoveryCodes"
                    wire:click="regenerateRecoveryCodes"
                >
                    <x-icon-refresh class="w-4 h-4" />
                    {{ __('Regenerate codes') }}
                </x-ui.button>
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <div class="alert alert-error">
                        <x-icon-x class="w-5 h-5 shrink-0" />
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 p-4 font-mono text-sm rounded-lg bg-base-200"
                        role="list"
                        aria-label="{{ __('Recovery codes') }}"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                role="listitem"
                                class="select-text"
                                wire:loading.class="opacity-50 animate-pulse"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-base-content/60">
                        {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-ui.card>
