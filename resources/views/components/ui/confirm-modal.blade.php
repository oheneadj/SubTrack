@props([
    'title' => 'Are you sure?',
    'message' => 'This action cannot be undone.',
    'confirmAction' => 'delete',
    'id' => 'confirm-modal-' . uniqid()
])

<div x-data="{ open: false }"
     x-on:open-modal.window="if ($event.detail.id === '{{ $id }}') open = true"
     x-on:close-modal.window="open = false"
     x-on:keydown.escape.window="open = false"
     x-show="open"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true"
     style="display: none;">

    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Overlay --}}
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
             @click="open = false"
             aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal Content --}}
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

            <div class="bg-white px-6 pt-6 pb-4 sm:p-8 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <x-icon-alert-triangle class="h-6 w-6 text-red-600" aria-hidden="true" />
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-5 sm:text-left">
                        <h3 class="text-lg leading-6 font-bold text-primary" id="modal-title">
                            {{ $title }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-secondary">
                                {{ $message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3">
                <button type="button"
                        class="btn btn-error btn-sm w-full sm:w-auto"
                        wire:click="{{ $confirmAction }}"
                        @click="open = false">
                    Confirm Action
                </button>
                <button type="button"
                        class="btn btn-ghost btn-sm w-full sm:w-auto mt-3 sm:mt-0"
                        @click="open = false">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
