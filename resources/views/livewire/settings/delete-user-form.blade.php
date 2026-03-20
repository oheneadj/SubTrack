<section class="mt-10">
    <div class="bg-red-50 rounded-2xl border border-red-200 p-6">
        <h3 class="text-lg font-bold text-red-700 mb-1">{{ __('Delete Account') }}</h3>
        <p class="text-sm text-red-600 mb-4">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}</p>

        <div x-data="{ showDeleteModal: false }">
            <button @click="showDeleteModal = true" class="btn btn-error btn-sm">
                <x-icon-trash class="w-4 h-4 mr-1" /> {{ __('Delete Account') }}
            </button>

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
                            <button type="button" @click="showDeleteModal = false" class="btn btn-ghost btn-sm">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" class="btn btn-error btn-sm">
                                {{ __('Delete Account') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
