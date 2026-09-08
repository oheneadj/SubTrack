<section class="mt-10">
    <x-ui.card class="border-red-200 bg-red-50/50">
        <h3 class="text-lg font-bold text-red-700 mb-1">{{ __('Delete Account') }}</h3>
        <p class="text-sm text-red-600 mb-4">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}</p>

        <div x-data="{ showDeleteModal: false }">
            <x-ui.button variant="error" @click="showDeleteModal = true">
                <x-icon-trash class="w-4 h-4 mr-1" /> {{ __('Delete Account') }}
            </x-ui.button>

            {{-- Delete Confirmation Modal --}}
            <div x-show="showDeleteModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                 @keydown.escape.window="showDeleteModal = false">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 mx-4"
                     @click.away="showDeleteModal = false">
                    <h3 class="text-lg font-bold text-primary mb-2">{{ __('Are you sure?') }}</h3>
                    <p class="text-sm text-secondary mb-6">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm.') }}
                    </p>

                    <form wire:submit="deleteUser">
                        <x-ui.form-input label="Password" model="password" type="password" placeholder="Enter your password" :error="$errors->first('password')" />

                        <div class="flex justify-end gap-3 mt-6">
                            <x-ui.button type="button" variant="ghost" @click="showDeleteModal = false">
                                {{ __('Cancel') }}
                            </x-ui.button>
                            <x-ui.button type="submit" variant="error">
                                {{ __('Delete Account') }}
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </x-ui.card>
</section>
