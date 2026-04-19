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
    <body class="bg-slate-100 font-sans antialiased text-slate-900">
        <div x-data="{ mobileNavOpen: false }" class="min-h-screen lg:flex">
            <aside :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-800 bg-slate-950 text-white transition-transform duration-200 lg:static lg:min-h-screen lg:translate-x-0">
                <div class="flex items-center justify-between px-5 py-5 lg:block lg:px-6">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Attendance Platform</p>
                        <h1 class="mt-2 text-2xl font-semibold">Mr. Putra Absence System</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-300">
                            {{ auth()->user()->role }}
                        </p>
                        <button @click="mobileNavOpen = false" type="button" class="rounded-lg p-2 text-slate-300 lg:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <nav class="grid gap-1 px-4 pb-5 lg:px-5">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                        Students
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('teachers.index') }}" class="{{ request()->routeIs('teachers.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                            Teachers
                        </a>
                        <a href="{{ route('teacher-schedules.index') }}" class="{{ request()->routeIs('teacher-schedules.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                            Jadwal Guru
                        </a>
                        <a href="{{ route('teacher-availabilities.index') }}" class="{{ request()->routeIs('teacher-availabilities.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                            Ketersediaan Guru
                        </a>
                        <a href="{{ route('packages.index') }}" class="{{ request()->routeIs('packages.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                            Packages
                        </a>
                        <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                            Payments
                        </a>
                    @else
                        <a href="{{ route('my-schedule.index') }}" class="{{ request()->routeIs('my-schedule.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                            Jadwal Saya
                        </a>
                        <a href="{{ route('my-availability.index') }}" class="{{ request()->routeIs('my-availability.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                            Ketersediaan Saya
                        </a>
                    @endif
                    <a href="{{ route('attendances.index') }}" class="{{ request()->routeIs('attendances.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                        Attendances
                    </a>
                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} rounded-xl px-4 py-3 text-sm font-medium">
                        Profile
                    </a>
                </nav>

                <div class="border-t border-slate-800 px-5 py-5">
                    <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="mt-1 text-sm text-slate-400">{{ auth()->user()->email }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4" data-confirm="Log out from this account?">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:text-white">
                            Log out
                        </button>
                    </form>
                </div>
            </aside>

            <div x-show="mobileNavOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden" @click="mobileNavOpen = false"></div>

            <main class="min-w-0 flex-1">
                <div class="border-b border-slate-200 bg-white px-4 py-4 shadow-sm lg:hidden">
                    <div class="flex items-center justify-between gap-3">
                        <button @click="mobileNavOpen = true" type="button" class="rounded-xl border border-slate-200 p-2 text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm1 4a1 1 0 100 2h12a1 1 0 100-2H4z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-semibold text-slate-900">Mr. Putra Absence System</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->name }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ auth()->user()->role }}</span>
                    </div>
                </div>

                <div class="px-4 py-5 sm:px-6 lg:px-10 lg:py-8">
                    @isset($header)
                        <header class="mb-4 rounded-3xl bg-white p-5 shadow-sm sm:mb-6 sm:p-6">
                            {{ $header }}
                        </header>
                    @endisset

                    @if (session('status'))
                        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>

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
