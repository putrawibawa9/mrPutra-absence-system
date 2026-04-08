<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Mr. Putra Absence System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <div class="w-full max-w-md">
                <div class="text-center">
                    <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Attendance Platform</p>
                    <h1 class="mt-3 text-3xl font-semibold text-slate-900">Mr. Putra Absence System</h1>
                    <p class="mt-2 text-sm text-slate-500">Student attendance and token-based payment system.</p>
                </div>

                <div class="mt-6 rounded-3xl bg-white px-6 py-6 shadow-xl shadow-slate-200/70 sm:px-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
