<div>
    @if(!$isModal)
        <x-ui.page-header :title="$pageTitle" subtitle="Define the web project and its client owner">
            <a href="{{ route('projects.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
            <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                <span wire:loading.remove>Save Project</span>
                <span wire:loading><span class="loading loading-spinner loading-xs"></span> Saving...</span>
            </button>
        </x-ui.page-header>
    @endif

    <div class="{{ $isModal ? 'p-1' : 'max-w-4xl mx-auto' }}">
        <div class="{{ $isModal ? '' : 'bg-white rounded-2xl border border-slate-200 p-8 shadow-sm' }}">
            @if($isModal)
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-slate-800">{{ $pageTitle }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Define the web project and its client owner.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Ownership --}}
                <div class="md:col-span-2">
                    <h3 class="text-sm font-bold text-secondary uppercase tracking-wider mb-2">Project Ownership</h3>
                    <hr class="border-slate-100 mb-6">
                </div>

                <div class="md:col-span-2">
                    <x-ui.form-select 
                        label="Client / Owner" 
                        model="client_id" 
                        :options="$clients" 
                        placeholder="Select a client..."
                        :error="$errors->first('client_id')" 
                    />
                </div>
                
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-sm font-bold text-secondary uppercase tracking-wider mb-2">Project Details</h3>
                    <hr class="border-slate-100 mb-6">
                </div>

                <div class="md:col-span-2">
                    <x-ui.form-input label="Project Name" model="project_name" placeholder="e.g. Corporate Website Redesign" :error="$errors->first('project_name')" />
                </div>
                
                <div class="md:col-span-2">
                    <x-ui.form-textarea label="Description (Optional)" model="description" rows="4" placeholder="Briefly describe the project scope..." :error="$errors->first('description')" />
                </div>
            </div>

            <div class="flex justify-end pt-8 mt-8 border-t border-slate-50 gap-3">
                @if($isModal)
                    <button type="button" @click="Livewire.dispatch('close-modal', {id: 'project-modal'})" class="btn btn-ghost btn-sm">Cancel</button>
                @else
                    <a href="{{ route('projects.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                @endif
                <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                    <span wire:loading.remove>
                        {{ $project && $project->exists ? 'Update Project' : 'Create Project' }}
                    </span>
                    <span wire:loading>
                        <span class="loading loading-spinner loading-xs"></span> Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
