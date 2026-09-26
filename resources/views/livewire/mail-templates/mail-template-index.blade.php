<div>
    <x-ui.page-header title="Email Templates" subtitle="Manage the content of emails sent to your clients." />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->templates as $template)
            <x-ui.card class="flex flex-col">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <x-icon-mail class="h-6 w-6" />
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">
                        {{ $template->slug }}
                    </span>
                </div>
                
                <h3 class="mb-2 text-lg font-bold text-slate-800">{{ $template->name }}</h3>
                <p class="mb-4 flex-1 text-sm text-slate-500 leading-relaxed">{{ $template->description }}</p>
                
                <div class="mt-auto pt-4 border-t border-slate-100">
                        <div class="flex flex-row gap-2">
                            <x-ui.button
                                as="a"
                                href="{{ route('mail-templates.preview', $template->slug) }}"
                                target="_blank"
                                variant="outline"
                                full
                                class="flex items-center gap-2 justify-center bg-white"
                            >
                                Preview
                            </x-ui.button>
                            <x-ui.button
                                wire:click="edit('{{ $template->ulid }}')"
                                full
                                class="flex items-center gap-2 justify-center"
                            >
                                Edit
                            </x-ui.button>
                        </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Edit Modal --}}
    @if($showEditModal && $editingTemplate)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 p-6">
                    <h3 class="text-xl font-bold text-slate-800">Edit Template: {{ $editingTemplate->name }}</h3>
                    <x-ui.button wire:click="$set('showEditModal', false)" variant="ghost" circle class="text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                        <x-icon-x class="h-6 w-6" />
                    </x-ui.button>
                </div>

                <form wire:submit="save">
                    <div class="p-6 space-y-6">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Line</label>
                            <input 
                                type="text" 
                                wire:model="editSubject" 
                                class="w-full rounded-xl border-slate-200 px-4 py-3 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Enter email subject..."
                            >
                            @error('editSubject') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="text-sm font-semibold text-slate-700">Email Body Text</label>
                                <span class="text-xs text-slate-400 italic">Formatting is handled by the layout</span>
                            </div>
                            <textarea 
                                wire:model="editBody" 
                                rows="10" 
                                class="w-full rounded-xl border-slate-200 px-4 py-3 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 font-mono text-sm leading-relaxed"
                                placeholder="Enter email body content..."
                            ></textarea>
                            @error('editBody') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-200">
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Available Variables</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($editingTemplate->variables as $variable)
                                    <code class="rounded bg-white border border-slate-200 px-2 py-1 text-xs font-mono text-blue-600 cursor-pointer hover:bg-blue-50" title="Click to copy (coming soon)">
                                        {{ $variable }}
                                    </code>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 p-6 bg-slate-50 rounded-b-2xl">
                        <div class="flex gap-2">
                            <x-ui.button type="button" wire:click="sendTest('{{ $editingTemplate->ulid }}')" variant="outline">
                                <x-icon-send class="w-4 h-4 mr-1" />
                                Send Test
                            </x-ui.button>
                            <x-ui.button type="button" wire:click="resetToDefault('{{ $editingTemplate->ulid }}')" variant="ghost" class="text-slate-500">
                                Reset to Default
                            </x-ui.button>
                        </div>
                        <div class="flex gap-3">
                            <x-ui.button type="button" wire:click="$set('showEditModal', false)" variant="ghost">
                                Cancel
                            </x-ui.button>
                            <x-ui.button type="submit" class="px-6">
                                Save Changes
                            </x-ui.button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
