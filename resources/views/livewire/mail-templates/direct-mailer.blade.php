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
        <a href="{{ route('mail-templates.index') }}" class="btn btn-ghost btn-sm flex items-center gap-2" wire:navigate>
            <x-icon-arrow-left class="w-4 h-4" />
            <span>Back to Templates</span>
        </a>
    </x-ui.page-header>

    @if(session('success'))
        <div class="alert alert-success mb-6 rounded-xl border-green-200 shadow-sm">
            <x-icon-circle-check class="w-5 h-5" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- ═══════════════ LEFT COLUMN: RECIPIENTS ═══════════════ --}}
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden h-[calc(100vh-220px)] flex flex-col sticky top-6">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2 mb-4">
                        <x-icon-users class="w-5 h-5 text-blue-500" />
                        Select Recipients
                    </h3>
                    
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-icon-search class="h-4 w-4 text-slate-400" />
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" 
                               class="input input-bordered w-full pl-10 h-10 text-sm rounded-xl focus:ring-blue-500 bg-white" 
                               placeholder="Search clients..." />
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ count($selectedClients) }} Selected
                        </span>
                        <label class="label cursor-pointer gap-2 p-0">
                            <span class="label-text font-bold text-slate-500 text-[11px] uppercase tracking-wider">Select All</span>
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-primary checkbox-xs rounded" />
                        </label>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-base-50">
                    @forelse($this->clients as $client)
                        <label class="flex items-center gap-4 p-4 hover:bg-blue-50/40 cursor-pointer transition-all group">
                            <input type="checkbox" wire:model.live="selectedClients" value="{{ $client->id }}" 
                                   class="checkbox checkbox-primary checkbox-sm rounded-md" />
                            
                            <div class="flex-1 flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 shrink-0 rounded-full bg-slate-100 text-slate-500 border border-slate-200 flex items-center justify-center font-bold text-xs group-hover:bg-blue-100 group-hover:text-blue-600 group-hover:border-blue-200 transition-colors">
                                    {{ strtoupper(substr($client->name, 0, 1)) }}
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-bold text-slate-700 text-sm truncate group-hover:text-blue-600 transition-colors">
                                        {{ $client->name }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 truncate font-medium">
                                        {{ $client->company_name ?: $client->email }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                <x-icon-search class="w-8 h-8 text-slate-200" />
                            </div>
                            <p class="text-sm font-bold text-slate-400">No matches found</p>
                            <p class="text-[11px] text-slate-400 mt-1 max-w-[150px] mx-auto">Try a different name or email address.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            @error('selectedClients') <span class="text-error text-[11px] font-bold uppercase tracking-wider px-2">{{ $message }}</span> @enderror
        </div>

        {{-- ═══════════════ RIGHT COLUMN: COMPOSER ═══════════════ --}}
        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-220px)] max-h-[900px]">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <x-icon-mail class="w-5 h-5 text-blue-500" />
                        Compose Message
                    </h3>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-white px-3 py-1 rounded-full border border-slate-100">
                            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                            Live Editor
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-8 space-y-8">
                    {{-- Template Selection --}}
                    <div class="group">
                        <label class="label py-0 mb-3">
                            <span class="label-text font-bold text-slate-500 uppercase tracking-wider text-[11px]">Use Template <span class="text-slate-300 font-normal ml-1 hover:text-blue-400 transition-colors">(Optional)</span></span>
                        </label>
                        <div class="relative">
                            <select wire:model.live="selectedTemplate" class="select select-bordered w-full rounded-2xl bg-white border-slate-200 focus:border-blue-500 transition-all font-bold text-slate-700 shadow-sm h-12">
                                <option value="">Draft from scratch...</option>
                                @foreach($this->templates as $template)
                                    <option value="{{ $template->slug }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-3 font-medium flex items-center gap-1.5 px-1">
                                <x-icon-info-circle class="w-4 h-4 text-blue-400" /> 
                                Note: Selecting a template will replace the current subject and body content.
                            </p>
                        </div>
                    </div>

                    <div class="divider before:bg-slate-50 after:bg-slate-50 opacity-60"></div>

                    {{-- Subject --}}
                    <div class="space-y-2">
                        <label class="label py-0">
                            <span class="label-text font-bold text-slate-500 uppercase tracking-wider text-[11px]">Subject Line</span>
                        </label>
                        <input wire:model="subject" type="text" 
                               class="input input-bordered w-full rounded-2xl h-12 font-bold text-slate-800 placeholder:text-slate-300 border-slate-200 focus:border-blue-500 shadow-sm"
                               placeholder="e.g. Important Update Regarding Your Subscription" />
                        @error('subject') <span class="text-error text-[11px] font-bold uppercase">{{ $message }}</span> @enderror
                    </div>

                    {{-- Body --}}
                    <div class="space-y-3">
                        <label class="label py-0">
                            <span class="label-text font-bold text-slate-500 uppercase tracking-wider text-[11px]">Message Content</span>
                        </label>
                        <div class="relative group">
                            <textarea 
                                x-ref="messageBody"
                                wire:model="body" 
                                class="textarea textarea-bordered w-full rounded-3xl p-8 text-base leading-relaxed bg-white border-slate-300/60 focus:border-blue-500 focus:ring-4 focus:ring-blue-50/50 transition-all resize-none shadow-inner min-h-[400px] font-medium text-slate-700 placeholder:text-slate-200"
                                placeholder="Start typing your personalized message here..."
                            ></textarea>
                            
                            {{-- Placeholder chips --}}
                            <div class="mt-6 flex flex-wrap gap-2 items-center px-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-2 border-r border-slate-200 pr-3">Quick Insert:</span>
                                @foreach(['{client_name}', '{company_name}', '{company_email}', '{app_name}'] as $var)
                                    <button 
                                        type="button"
                                        @click="insertPlaceholder('{{ $var }}')"
                                        class="badge bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-600 border-none transition-all cursor-pointer py-4 px-4 rounded-xl text-[11px] font-bold shadow-sm active:scale-95"
                                    >
                                        {{ $var }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @error('body') <span class="text-error text-[11px] font-bold uppercase">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-5">
                        <div class="flex -space-x-3">
                            @php $displayLimit = 4; @endphp
                            @forelse(array_slice($selectedClients, 0, $displayLimit) as $index => $cid)
                                @php $recipient = \App\Models\Client::find($cid); @endphp
                                <div class="w-10 h-10 rounded-full border-4 border-slate-50 bg-white ring-1 ring-slate-200 flex items-center justify-center font-bold text-xs text-blue-600 shadow-sm overflow-hidden z-[{{ 10 - $index }}]">
                                    {{ strtoupper(substr($recipient?->name ?? '?', 0, 1)) }}
                                </div>
                            @empty
                                <div class="w-10 h-10 rounded-full border-4 border-slate-50 bg-slate-200 ring-1 ring-slate-300 flex items-center justify-center text-slate-400 z-10">
                                    <x-icon-users class="w-4 h-4" />
                                </div>
                            @endforelse
                            @if(count($selectedClients) > $displayLimit)
                                <div class="w-10 h-10 rounded-full border-4 border-slate-50 bg-slate-800 text-white flex items-center justify-center font-bold text-[10px] z-0 shadow-sm">
                                    +{{ count($selectedClients) - $displayLimit }}
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-800">
                                {{ count($selectedClients) ?: 'No' }} recipients selected
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                Finalizing preparation...
                            </span>
                        </div>
                    </div>

                    <button 
                        wire:click="send" 
                        wire:loading.attr="disabled" 
                        class="btn btn-primary btn-lg px-12 rounded-2xl shadow-xl shadow-blue-500/20 flex items-center gap-3 group transition-all"
                        @if(empty($selectedClients)) disabled @endif
                    >
                        <span wire:loading.remove wire:target="send" class="flex items-center gap-3">
                             <span class="font-bold tracking-tight">Send Message Now</span>
                             <x-icon-send class="w-5 h-5 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" />
                        </span>
                        <span wire:loading wire:target="send">
                            <span class="loading loading-spinner loading-sm"></span> 
                            <span class="font-bold">Broadcasting...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
