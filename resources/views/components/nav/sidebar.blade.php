<aside class="w-64 bg-slate-900 text-slate-400 flex-shrink-0 flex flex-col h-full border-r border-slate-800 hidden lg:flex">
    <div class="p-6 flex items-center gap-3">
        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold">S</div>
        <span class="text-xl font-bold text-white tracking-tight">SubTrack</span>
    </div>

    <nav class="flex-1 px-4 py-4 space-y-1">
        <x-nav.item href="{{ route('dashboard') }}" label="Dashboard" icon="layout-dashboard" :active="request()->routeIs('dashboard')" />
        
        <div class="pt-4 pb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Management</div>
        
        <x-nav.item href="{{ route('clients.index') }}" label="Clients" icon="users" :active="request()->routeIs('clients.*')" />
        <x-nav.item href="{{ route('projects.index') }}" label="Projects" icon="folder" :active="request()->routeIs('projects.*')" />
        <x-nav.item href="{{ route('providers.index') }}" label="Providers" icon="building" :active="request()->routeIs('providers.*')" />
        <x-nav.item href="{{ route('subscriptions.index') }}" label="Subscriptions" icon="refresh" :active="request()->routeIs('subscriptions.*')" />
        <x-nav.item href="{{ route('renewals.index') }}" label="Renewals" icon="calendar-due" :active="request()->routeIs('renewals.*')" />
        <x-nav.item href="{{ route('invoices.index') }}" label="Invoices" icon="file-invoice" :active="request()->routeIs('invoices.*')" />
        
        @if(auth()->user()->isSuperAdmin())
            <div class="pt-4 pb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Administration</div>
            <x-nav.item href="{{ route('users.index') }}" label="Users" icon="users" :active="request()->routeIs('users.*')" />
            <x-nav.item href="{{ route('activity-logs.index') }}" label="Activity Logs" icon="clipboard-list" :active="request()->routeIs('activity-logs.*')" />
            <x-nav.item href="{{ route('mail-templates.index') }}" label="Mail Templates" icon="mail" :active="request()->routeIs('mail-templates.*')" />
            <x-nav.item href="{{ route('mail-mailer.index') }}" label="Client Mailer" icon="send" :active="request()->routeIs('mail-mailer.*')" />
        @endif

        <div class="pt-6 mt-auto space-y-1">
            <x-nav.item href="{{ route('profile.edit') }}" label="Profile" icon="users" :active="request()->routeIs('profile.*')" />
            <x-nav.item href="{{ route('settings.index') }}" label="Settings" icon="settings" :active="request()->routeIs('settings.*')" />
        </div>
    </nav>
</aside>
