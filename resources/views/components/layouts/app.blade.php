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

<body class="font-sans antialiased bg-slate-50 text-slate-900">
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

    {{-- Global Flash Toasts --}}
    <x-ui.toast type="success" :message="session('success')" />
    <x-ui.toast type="error" :message="session('error')" />

    @stack('scripts')

</body>

</html>