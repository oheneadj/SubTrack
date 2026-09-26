<div x-data="{ 
    insertPlaceholder(value) {
        const textarea = this.$refs.messageBody;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        
        textarea.value = before + value + after;
        textarea.selectionStart = textarea.selectionEnd = start + value.length;
        textarea.focus();
        
        // Update Livewire model
        this.$wire.set('body', textarea.value);
    }
}" @insert-placeholder.window="insertPlaceholder($event.detail.value)">
    <x-ui.page-header title="Direct Mailer" subtitle="Compose and send personalized messages to your clients.">
        <x-ui.button as="a" variant="ghost" href="{{ route('mail-templates.index') }}" wire:navigate>
            <x-icon-arrow-left class="w-4 h-4" />
            <span>Back to Templates</span>
        </x-ui.button>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- ═══════════════ LEFT COLUMN: RECIPIENTS ═══════════════ --}}
        <div class="lg:col-span-4 space-y-4">
            <x-ui.card :padding="false" class="h-[calc(100vh-220px)] flex flex-col sticky top-6">
                <div class="p-5 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2 mb-4">
                        <x-icon-users class="w-5 h-5 text-blue-500" />
                        Select Recipients
                    </h3>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-icon-search class="h-4 w-4 text-slate-400" />
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                               class="input input-bordered w-full pl-10"
                               placeholder="Search clients..." />
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            {{ count($selectedClients) }} Selected
                        </span>
                        <label class="flex items-center gap-2 p-0">
                            <span class="label-text-alt font-medium uppercase tracking-wider">Select All</span>
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-primary checkbox-xs" />
                        </label>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-slate-200">
                    @forelse($this->clients as $client)
                        <label class="flex items-center gap-4 p-4 hover:bg-blue-50/40 cursor-pointer transition-all group">
                            <input type="checkbox" wire:model.live="selectedClients" value="{{ $client->ulid }}"
                                   class="checkbox checkbox-primary checkbox-sm rounded-md" />
                            
                            <div class="flex-1 flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 shrink-0 rounded-full bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center font-bold text-xs group-hover:bg-blue-100 group-hover:text-blue-600 group-hover:border-blue-200 transition-colors">
                                    {{ strtoupper(substr($client->name, 0, 1)) }}
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-bold text-slate-700 text-sm truncate group-hover:text-blue-600 transition-colors">
                                        {{ $client->name }}
                                    </span>
                                    <span class="text-xs text-slate-400 truncate font-medium">
                                        {{ $client->company_name ?: $client->email }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    @empty
                        <x-ui.empty-state
                            icon="search"
                            title="No matches found"
                            message="Try a different name or email address."
                        />
                    @endforelse
                </div>

                @if($this->clients->hasPages())
                    <div class="p-3 border-t border-slate-100">
                        {{ $this->clients->links() }}
                    </div>
                @endif
            </x-ui.card>
            @error('selectedClients') <span class="label-text-alt text-error px-2">{{ $message }}</span> @enderror
        </div>

        {{-- ═══════════════ RIGHT COLUMN: COMPOSER ═══════════════ --}}
        <div class="lg:col-span-8">
            <x-ui.card :padding="false" class="flex flex-col h-[calc(100vh-220px)] max-h-[900px]">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <x-icon-mail class="w-5 h-5 text-blue-500" />
                        Compose Message
                    </h3>
                    <x-ui.button type="button" variant="outline" size="sm" wire:click="openPreview">
                        <x-icon-eye class="w-4 h-4" />
                        <span>Preview</span>
                    </x-ui.button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    {{-- Template Selection --}}
                    <div>
                        <label class="label py-0 mb-2">
                            <span class="label-text">Use Template <span class="label-text-alt font-normal">(Optional)</span></span>
                        </label>
                        <select
                            x-on:change="
                                if (@js($hasManualEdits) && $el.value && !confirm('You have an unsaved draft. Selecting a template will replace the current subject and message. Continue?')) {
                                    $el.value = @js($selectedTemplate ?? '');
                                    return;
                                }
                                $wire.set('selectedTemplate', $el.value);
                            "
                            class="select select-bordered w-full"
                        >
                            <option value="">Draft from scratch...</option>
                            @foreach($this->templates as $template)
                                <option value="{{ $template->slug }}" @selected($selectedTemplate === $template->slug)>{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <p class="label-text-alt mt-2 flex items-center gap-1.5">
                            <x-icon-info-circle class="w-4 h-4 text-blue-400" />
                            Selecting a template replaces the current subject and body content. You'll be asked to confirm if you've already started a draft.
                        </p>
                    </div>

                    <div class="divider"></div>

                    {{-- Subject --}}
                    <div class="form-control">
                        <label class="label py-0">
                            <span class="label-text">Subject Line</span>
                        </label>
                        <input wire:model.blur="subject" type="text"
                               class="input input-bordered w-full"
                               placeholder="e.g. Important Update Regarding Your Subscription" />
                        @error('subject') <span class="label-text-alt text-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Body --}}
                    <div class="form-control">
                        <label class="label py-0">
                            <span class="label-text">Message Content</span>
                        </label>
                        <textarea
                            x-ref="messageBody"
                            wire:model.blur="body"
                            class="textarea textarea-bordered w-full min-h-[350px]"
                            placeholder="Start typing your personalized message here..."
                        ></textarea>

                        {{-- Placeholder chips --}}
                        <div class="mt-3 flex flex-wrap gap-2 items-center">
                            <span class="label-text-alt font-medium uppercase tracking-wider mr-1">Quick Insert:</span>
                            @foreach(['{client_name}', '{company_name}', '{company_email}', '{app_name}'] as $var)
                                <button
                                    type="button"
                                    @click="insertPlaceholder('{{ $var }}')"
                                    class="badge bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-600 border-none transition-colors cursor-pointer py-3 px-3 text-xs font-semibold"
                                >
                                    {{ $var }}
                                </button>
                            @endforeach
                        </div>
                        @error('body') <span class="label-text-alt text-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="flex -space-x-3">
                            @php $displayLimit = 4; @endphp
                            @forelse(array_slice($selectedClients, 0, $displayLimit) as $index => $cid)
                                @php $recipient = $this->selectedClientModels->get($cid); @endphp
                                <div class="w-9 h-9 rounded-full border-2 border-slate-50 bg-white ring-1 ring-slate-200 flex items-center justify-center font-bold text-xs text-blue-600 overflow-hidden z-[{{ 10 - $index }}]">
                                    {{ strtoupper(substr($recipient?->name ?? '?', 0, 1)) }}
                                </div>
                            @empty
                                <div class="w-9 h-9 rounded-full border-2 border-slate-50 bg-slate-200 ring-1 ring-slate-300 flex items-center justify-center text-slate-400 z-10">
                                    <x-icon-users class="w-4 h-4" />
                                </div>
                            @endforelse
                            @if(count($selectedClients) > $displayLimit)
                                <div class="w-9 h-9 rounded-full border-2 border-slate-50 bg-slate-800 text-white flex items-center justify-center font-bold text-xs z-0">
                                    +{{ count($selectedClients) - $displayLimit }}
                                </div>
                            @endif
                        </div>
                        <span class="text-sm font-semibold text-slate-700">
                            {{ count($selectedClients) ?: 'No' }} recipients selected
                        </span>
                    </div>

                    <x-ui.button
                        wire:click="send"
                        wire:loading.attr="disabled"
                        size="lg"
                        :disabled="empty($selectedClients)"
                    >
                        <span wire:loading.remove wire:target="send" class="flex items-center gap-2">
                             <span>Send Message Now</span>
                             <x-icon-send class="w-4 h-4" />
                        </span>
                        <span wire:loading wire:target="send">
                            <span class="loading loading-spinner loading-sm"></span>
                            <span>Broadcasting...</span>
                        </span>
                    </x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Preview Modal --}}
    @if($showPreview)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 p-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Message Preview</h3>
                        <p class="label-text-alt mt-1">
                            @if($this->previewClient)
                                Showing how this will look for <span class="font-semibold text-slate-600">{{ $this->previewClient->name }}</span>
                            @else
                                Select a recipient to see placeholders rendered with real data.
                            @endif
                        </p>
                    </div>
                    <x-ui.button type="button" wire:click="closePreview" variant="ghost" circle class="text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                        <x-icon-x class="h-5 w-5" />
                    </x-ui.button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <p class="label-text-alt mb-1">Subject</p>
                        <p class="font-semibold text-slate-800">{{ $this->previewRendered['subject'] ?: '(no subject)' }}</p>
                    </div>
                    <div class="divider"></div>
                    <div>
                        <p class="label-text-alt mb-1">Body</p>
                        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $this->previewRendered['body'] ?: '(no message content)' }}</p>
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 p-6 bg-slate-50 rounded-b-2xl">
                    <x-ui.button type="button" wire:click="closePreview" variant="ghost">Close</x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
