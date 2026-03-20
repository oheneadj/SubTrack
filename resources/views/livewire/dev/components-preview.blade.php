<div class="space-y-12 pb-20">
    <x-ui.page-header title="Component Preview" subtitle="Visual documentation of SubTrack UI library">
        <button class="btn btn-primary btn-sm">Primary Action</button>
        <button class="btn btn-outline btn-sm">Secondary</button>
    </x-ui.page-header>

    {{-- Stats section --}}
    <section class="space-y-4">
        <h2 class="text-lg font-bold text-primary">Stat Cards</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-ui.stat-card label="Critical Subscriptions" value="12" icon="alert-triangle" variant="critical" />
            <x-ui.stat-card label="Expiring Soon" value="45" icon="calendar-due" variant="warning" />
            <x-ui.stat-card label="Active Subscriptions" value="128" icon="circle-check" variant="healthy" />
            <x-ui.stat-card label="Total Clients" value="89" icon="users" />
        </div>
    </section>

    {{-- Badges --}}
    <section class="space-y-4">
        <h2 class="text-lg font-bold text-primary">Badges</h2>
        <div class="bg-white p-6 rounded-xl border border-slate-200">
            <div class="flex flex-wrap gap-8">
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-secondary uppercase">Subscription Status</p>
                    <div class="flex gap-2">
                        <x-ui.badge-status status="Active" />
                        <x-ui.badge-status status="Expiring" />
                        <x-ui.badge-status status="Expired" />
                        <x-ui.badge-status status="Cancelled" />
                    </div>
                </div>
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-secondary uppercase">Payment Status</p>
                    <div class="flex gap-2">
                        <x-ui.badge-payment status="Pending" />
                        <x-ui.badge-payment status="Invoiced" />
                        <x-ui.badge-payment status="Paid" />
                        <x-ui.badge-payment status="Renewed" />
                        <x-ui.badge-payment status="Lapsed" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Forms --}}
    <section class="space-y-4">
        <h2 class="text-lg font-bold text-primary">Form Components</h2>
        <div class="bg-white p-8 rounded-xl border border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <x-ui.form-input label="Standard Input" model="inputText" placeholder="Type something..." />
                    <x-ui.form-input label="Input with Prefix" model="inputText" prefix="$" placeholder="0.00" />
                    <x-ui.form-input label="Input with Error" model="inputText" error="This field is required" />
                </div>
                <div class="space-y-6">
                    <x-ui.form-select 
                        label="Select Dropdown" 
                        model="selectValue" 
                        :options="['opt1' => 'Option One', 'opt2' => 'Option Two']" 
                    />
                    <x-ui.form-textarea label="Textarea Content" model="textareaText" placeholder="Enter notes..." />
                </div>
            </div>
        </div>
    </section>

    {{-- Table --}}
    <section class="space-y-4">
        <h2 class="text-lg font-bold text-primary">Data Table</h2>
        <x-ui.data-table :headers="['ID', 'Name', 'Status', 'Record Date', '']">
            @for ($i = 1; $i <= 3; $i++)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="font-mono text-xs">#00{{ $i }}</td>
                    <td class="font-semibold">Sample Item {{ $i }}</td>
                    <td><x-ui.badge-status status="Active" /></td>
                    <td class="text-secondary">2026-03-{{ 10 + $i }}</td>
                    <td class="text-right">
                        <x-ui.action-menu editAction="edit({{ $i }})" deleteAction="confirmDelete({{ $i }})" />
                    </td>
                </tr>
            @endfor
        </x-ui.data-table>
    </section>

    {{-- Empty State & Modals --}}
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200">
            <h2 class="text-lg font-bold text-primary p-6 border-b border-slate-100">Empty State</h2>
            <x-ui.empty-state 
                icon="search" 
                title="No items found" 
                message="Try adjusting your filters or adding a new search term." 
            >
                <button class="btn btn-primary btn-sm">Clear Search</button>
            </x-ui.empty-state>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-primary mb-4">Interactions</h2>
            <div class="space-y-4">
                <p class="text-sm text-secondary">Click the button below to test the confirmation modal.</p>
                <button class="btn btn-error btn-sm" 
                        @click="$dispatch('open-modal', { id: 'delete-sample' })">
                    Open Delete Modal
                </button>
            </div>
        </div>
    </section>

    <x-ui.confirm-modal 
        id="delete-sample"
        title="Delete this component?" 
        message="This will permanently delete this record from the database. This action is irreversible." 
        confirmAction="delete" 
    />
</div>
