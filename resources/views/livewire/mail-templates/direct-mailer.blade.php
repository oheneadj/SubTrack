<div>
    <x-ui.page-header title="Client Mailer" subtitle="Send personalized emails to single or multiple clients.">
        <a href="{{ route('mail-templates.index') }}" class="btn btn-outline btn-sm">
            <x-icon-edit class="w-4 h-4 mr-1" /> Manage Templates
        </a>
    </x-ui.page-header>

    @if(session('success'))
        <div class="alert alert-success mb-6">
            <x-icon-check class="w-5 h-5" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Client Selection -->
        <div class="lg:col-span-4 flex flex-col gap-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <x-icon-users class="w-5 h-5 mr-2" /> Select Clients
                    </h3>
                    
                    <div class="form-control mb-4">
                        <div class="input-group">
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search clients..." class="input input-bordered w-full" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-2 px-2">
                        <span class="text-sm font-medium text-base-content/70">{{ count($selectedClients) }} selected</span>
                        <label class="label cursor-pointer gap-2">
                            <span class="label-text">Select All</span>
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-primary checkbox-sm" />
                        </label>
                    </div>

                    <div class="max-h-[500px] overflow-y-auto border border-base-200 rounded-lg">
                        <table class="table table-sm table-zebra w-full">
                            <thead>
                                <tr>
                                    <th class="w-10"></th>
                                    <th>Client</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->clients as $client)
                                    <tr>
                                        <td>
                                            <input type="checkbox" wire:model.live="selectedClients" value="{{ $client->id }}" class="checkbox checkbox-primary checkbox-xs" />
                                        </td>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-sm">{{ $client->name }}</span>
                                                <span class="text-xs opacity-60">{{ $client->company_name ?: $client->email }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-4 text-base-content/50">No clients found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @error('selectedClients') <span class="text-error text-xs mt-2">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- Mail Composer -->
        <div class="lg:col-span-8">
            <div class="card bg-base-100 shadow-sm border border-base-200 h-full">
                <div class="card-body p-6">
                    <h3 class="text-lg font-bold mb-6 flex items-center">
                        <x-icon-mail class="w-5 h-5 mr-2" /> Compose Message
                    </h3>

                    <div class="grid grid-cols-1 gap-6">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-medium">Use Template (Optional)</span>
                            </label>
                            <select wire:model.live="selectedTemplate" class="select select-bordered w-full">
                                <option value="">Select a template to pre-fill content...</option>
                                @foreach($this->templates as $template)
                                    <option value="{{ $template->slug }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                            <label class="label">
                                <span class="label-text-alt text-base-content/50 italic">Choosing a template will overwrite current subject and body.</span>
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-medium">Subject</span>
                            </label>
                            <input wire:model="subject" type="text" class="input input-bordered w-full" placeholder="Enter email subject..." />
                            @error('subject') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-medium">Message Body</span>
                            </label>
                            <textarea wire:model="body" rows="12" class="textarea textarea-bordered w-full font-serif" placeholder="Write your message here..."></textarea>
                            @error('body') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="text-xs font-semibold text-base-content/50">Supported Placeholders:</span>
                                @foreach(['{client_name}', '{company_name}', '{company_email}', '{app_name}'] as $var)
                                    <span class="badge badge-neutral badge-sm">{{ $var }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card-actions justify-end mt-8 pt-6 border-t border-base-200">
                        <button wire:click="send" wire:loading.attr="disabled" class="btn btn-primary px-8">
                            <span wire:loading.remove class="flex items-center">
                                <x-icon-send class="w-4 h-4 mr-2" /> Send to {{ count($selectedClients) }} Clients
                            </span>
                            <span wire:loading>
                                <span class="loading loading-spinner loading-xs"></span> Sending...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
