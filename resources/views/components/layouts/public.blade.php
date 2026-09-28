<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = \App\Models\Setting::get('app_name') ?: config('app.name', 'SubTrack');
        $companyName = \App\Models\Setting::get('company_name') ?: (\App\Models\Setting::get('business_name') ?: $appName);
        $companyLogoPath = \App\Models\Setting::get('logo_path');
    @endphp
    <title>{{ filled($title ?? null) ? $title.' — '.$companyName : $companyName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Minimal header -->
        <header class="bg-white border-b border-slate-200 px-6 py-4">
            <div class="max-w-2xl mx-auto flex items-center justify-between">
                <span class="flex items-center gap-2">
                    @if($companyLogoPath)
                        <img src="{{ Storage::url($companyLogoPath) }}" alt="" class="h-8 w-auto max-w-[160px] object-contain">
                    @else
                        <span class="text-lg font-bold text-slate-800">{{ $companyName }}</span>
                    @endif
                </span>
                @if(isset($headerRight))
                    {{ $headerRight }}
                @endif
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-4 py-10">
            <div class="max-w-2xl mx-auto">
                {{ $slot }}
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-slate-400">
            Secured payment powered by {{ $companyName }}
        </footer>
    </div>
</body>
</html>
