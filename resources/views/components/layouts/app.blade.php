<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $companyName = \App\Models\Setting::get('business_name');
        $appName = \App\Models\Setting::get('app_name') ?: config('app.name', 'SubTrack');
        $brandingName = $companyName ? "{$companyName} - {$appName}" : $appName;
    @endphp
    <title>{{ filled($title ?? null) ? $title.' - '.$brandingName : $brandingName }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    @stack('styles')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 text-slate-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <x-nav.sidebar />

        <!-- Main Content -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Topbar -->
            <x-nav.topbar />

            <main class="p-6 md:px-42">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Notification Slideover --}}
    <x-nav.notification-drawer />

    {{-- Global Flash Toasts (page-load only — session flash doesn't reach a
         Livewire action's response, since that only re-renders the component's
         own DOM, not this layout) --}}
    <x-ui.toast type="success" :message="session('success')" />
    <x-ui.toast type="warning" :message="session('warning')" />
    <x-ui.toast type="error" :message="session('error')" />

    {{-- Live Toasts — for Livewire actions that don't cause a page navigation.
         Dispatch with: $this->dispatch('notify', type: 'success', message: '...') --}}
    <div
        x-data="{ toasts: [] }"
        x-on:notify.window="
            const toast = { id: Date.now() + Math.random(), type: $event.detail.type ?? 'success', message: $event.detail.message };
            toasts.push(toast);
            setTimeout(() => { toasts = toasts.filter(t => t.id !== toast.id) }, 4000);
        "
        class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="true" x-transition class="alert" :class="'alert-' + toast.type">
                <template x-if="toast.type === 'success'"><x-icon-check class="w-5 h-5" /></template>
                <template x-if="toast.type !== 'success'"><x-icon-alert-triangle class="w-5 h-5" /></template>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    @stack('scripts')

</body>

</html>