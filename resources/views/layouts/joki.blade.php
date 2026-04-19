<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Manajemen Joki Mr. Putra')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-900">
        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Manajemen Joki Mr. Putra</h1>
                    <p class="text-sm text-slate-500">Aplikasi sederhana untuk mengelola proyek dan progress mahasiswa.</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('joki.dashboard') }}" class="{{ request()->routeIs('joki.dashboard') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }} rounded-lg px-4 py-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }} rounded-lg px-4 py-2 text-sm font-medium">
                        Projects
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700">
                            Dashboard Internal
                        </a>
                    @elseif (Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700">
                            Login
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-medium">Periksa kembali input yang Anda isi.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <script>
            document.addEventListener('submit', function (event) {
                const form = event.target;
                const message = form.dataset.confirm;

                if (! message || form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();

                if (window.confirm(message)) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
        </script>
    </body>
</html>
