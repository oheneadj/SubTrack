<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error | SubTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 text-red-600 mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 9v2m0 4v.01" />
                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                </svg>
            </div>
            <h1 class="text-6xl font-extrabold text-slate-900 tracking-tight mb-2">500</h1>
            <h2 class="text-2xl font-bold text-slate-700 mb-4">Something went wrong</h2>
            <p class="text-slate-500 mb-8 max-w-xs mx-auto">
                Our servers are having some trouble. We've logged the error and are looking into it.
            </p>
            <div class="flex justify-center gap-4">
                <button onclick="window.location.reload()" class="inline-flex items-center px-6 py-3 border border-slate-300 text-base font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200">
                    Try Again
                </button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition-all duration-200">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
