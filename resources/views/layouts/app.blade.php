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
    @php
        $navSections = [
            [
                'label' => 'Overview',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
                ],
            ],
            [
                'label' => 'Students',
                'items' => [
                    ['label' => 'All Students', 'route' => 'students.index', 'pattern' => 'students.*'],
                ],
            ],
        ];

        if (auth()->user()->isAdmin()) {
            $navSections[] = [
                'label' => 'Teaching',
                'items' => [
                    ['label' => 'Kelas', 'route' => 'classrooms.index', 'pattern' => 'classrooms.*'],
                    ['label' => 'Attendances', 'route' => 'attendances.index', 'pattern' => 'attendances.*'],
                    ['label' => 'Teachers', 'route' => 'teachers.index', 'pattern' => 'teachers.*'],
                    ['label' => 'Jadwal Guru', 'route' => 'teacher-schedules.index', 'pattern' => 'teacher-schedules.*'],
                    ['label' => 'Ketersediaan Guru', 'route' => 'teacher-availabilities.index', 'pattern' => 'teacher-availabilities.*'],
                ],
            ];

            $navSections[] = [
                'label' => 'Academics',
                'items' => [
                    ['label' => 'Modules', 'route' => 'learning-modules.index', 'pattern' => 'learning-modules.*'],
                    ['label' => 'Link Materi', 'route' => 'material-links.index', 'pattern' => 'material-links.*'],
                ],
            ];

            $navSections[] = [
                'label' => 'Finance',
                'items' => [
                    ['label' => 'Payments', 'route' => 'payments.index', 'pattern' => 'payments.*'],
                    ['label' => 'Expenses', 'route' => 'expenses.index', 'pattern' => 'expenses.*'],
                    ['label' => 'Expense Categories', 'route' => 'expense-categories.index', 'pattern' => 'expense-categories.*'],
                    ['label' => 'Cash Flow', 'route' => 'cash-flow.index', 'pattern' => 'cash-flow.*'],
                ],
            ];
        } else {
            $navSections[] = [
                'label' => 'Teaching',
                'items' => [
                    ['label' => 'Kelas', 'route' => 'classrooms.index', 'pattern' => 'classrooms.*'],
                    ['label' => 'Attendances', 'route' => 'attendances.index', 'pattern' => 'attendances.*'],
                    ['label' => 'Jadwal Saya', 'route' => 'my-schedule.index', 'pattern' => 'my-schedule.*'],
                    ['label' => 'Ketersediaan Saya', 'route' => 'my-availability.index', 'pattern' => 'my-availability.*'],
                ],
            ];
        }

        $navSections[] = [
            'label' => 'Account',
            'items' => [
                ['label' => 'Profile', 'route' => 'profile.edit', 'pattern' => 'profile.*'],
            ],
        ];
    @endphp
    <body class="bg-slate-100 font-sans antialiased text-slate-900">
        <div x-data="{ mobileNavOpen: false }" class="min-h-screen lg:flex">
            <aside :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-800 bg-slate-950 text-white transition-transform duration-200 lg:static lg:min-h-screen lg:translate-x-0">
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

                <nav class="flex-1 space-y-5 overflow-y-auto px-4 pb-5 lg:px-5">
                    @foreach ($navSections as $section)
                        @php
                            $sectionIsActive = collect($section['items'])->contains(fn ($item) => request()->routeIs($item['pattern']));
                            $activeSectionItem = collect($section['items'])->first(fn ($item) => request()->routeIs($item['pattern']));
                        @endphp
                        <div x-data="{ open: {{ $sectionIsActive ? 'true' : 'false' }} }" class="rounded-2xl border border-white/5 bg-white/[0.03]">
                            <button
                                type="button"
                                @click="open = ! open"
                                class="flex w-full items-center justify-between gap-3 rounded-2xl px-4 py-3 text-left"
                            >
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">{{ $section['label'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-white">
                                        {{ $activeSectionItem['label'] ?? 'Open menu' }}
                                    </p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-transition.opacity.duration.150ms class="space-y-1 px-2 pb-2">
                                @foreach ($section['items'] as $item)
                                    <a
                                        href="{{ route($item['route']) }}"
                                        class="{{ request()->routeIs($item['pattern']) ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition"
                                    >
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
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
