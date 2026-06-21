<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - Dashboard</title>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex items-center justify-center min-h-screen">
    <div class="text-center">
        <h1 class="text-2xl font-medium mb-6">Dashboard</h1>
        <p class="text-[#706f6c] dark:text-[#A1A09A] mb-8">Welcome, {{ Auth::user()->name }}!</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Logout
            </button>
        </form>
    </div>
</body>
</html>
