<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $companyName = \App\Models\Setting::get('business_name');
    $appName = \App\Models\Setting::get('app_name') ?: config('app.name', 'SubTrack');
    $brandingName = $companyName ? "{$companyName} - {$appName}" : $appName;
@endphp
<title>{{ filled($title ?? null) ? $title.' - '.$brandingName : $brandingName }}</title>

@php
    $logoPath = \App\Models\Setting::get('logo_path');
@endphp
@if($logoPath)
    <link rel="icon" href="{{ Storage::url($logoPath) }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ Storage::url($logoPath) }}">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

