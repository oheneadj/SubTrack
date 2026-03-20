<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Email Templates</h1>
            <p class="text-slate-500">Manage the content of emails sent to your clients.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->templates as $template)
            <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
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
                        <button 
                            wire:click="edit({{ $template->id }})" 
                            class="flex items-center gap-2 justify-center btn btn-primary btn-sm w-full"
                        >
                            <x-icon-edit class="mr-1 h-4 w-4" />
                            Edit Template
                        </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Edit Modal --}}
    @if($showEditModal && $editingTemplate)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 p-6">
                    <h3 class="text-xl font-bold text-slate-800">Edit Template: {{ $editingTemplate->name }}</h3>
                    <button wire:click="$set('showEditModal', false)" class="rounded-lg p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                        <x-icon-x class="h-6 w-6" />
                    </button>
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
                            <button type="button" wire:click="sendTest({{ $editingTemplate->id }})" class="btn btn-outline btn-sm">
                                <x-icon-send class="w-4 h-4 mr-1" />
                                Send Test
                            </button>
                            <button type="button" wire:click="resetToDefault({{ $editingTemplate->id }})" class="btn btn-ghost btn-sm text-slate-500">
                                Reset to Default
                            </button>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" wire:click="$set('showEditModal', false)" class="btn btn-ghost btn-sm">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-6">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
