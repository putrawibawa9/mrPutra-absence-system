<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Students</h2>
                <p class="text-sm text-slate-500">Manage enrolled students and review their remaining sessions.</p>
            </div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('students.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Student</a>
            @endif
        </div>
    </x-slot>

    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('students.index') }}" class="flex flex-col gap-3 xl:flex-row">
            <div class="flex-1">
                <x-input-label for="search" value="Search Student" />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['search'] ?? ''" placeholder="Search by name, phone, email, book info, or program" />
            </div>
            <div class="xl:w-56">
                <x-input-label for="program_type" value="Program" />
                <select id="program_type" name="program_type" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">All programs</option>
                    @foreach (\App\Models\Student::programOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['program_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:w-64">
                <x-input-label for="sort_tokens" value="Sort Token" />
                <select id="sort_tokens" name="sort_tokens" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">Newest first</option>
                    <option value="lowest" @selected(($filters['sort_tokens'] ?? '') === 'lowest')>Token terkecil ke terbesar</option>
                    <option value="highest" @selected(($filters['sort_tokens'] ?? '') === 'highest')>Token terbesar ke terkecil</option>
                </select>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <button type="submit" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                    Search
                </button>
                <a href="{{ route('students.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="space-y-4 md:hidden">
        @forelse ($students as $student)
            @php
                $remainingSessions = $student->payments_sum_remaining_sessions ?? $student->getRemainingSessions();
                $remainingClass = $remainingSessions <= 0
                    ? 'bg-rose-100 text-rose-700'
                    : ($remainingSessions <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
            @endphp
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-slate-900">{{ $student->name }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $student->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $student->statusLabel() }}
                            </span>
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                {{ $student->programLabel() }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $student->phone }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $student->email ?: '-' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $remainingClass }}">
                        {{ $remainingSessions }}
                    </span>
                </div>
                @if ($student->getOutstandingPaymentDebt() > 0)
                    <div class="mt-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm">
                        <p class="text-amber-700"><span class="font-semibold">Total Debt:</span> {{ $student->getOutstandingPaymentDebtLabel() }}</p>
                    </div>
                @endif
                @if ($student->getTokenDebtCount() > 0)
                    <div class="mt-3 rounded-2xl bg-slate-100 px-4 py-3 text-sm">
                        <p class="text-slate-700"><span class="font-semibold">Token Debt:</span> {{ $student->getTokenDebtLabel() }}</p>
                    </div>
                @endif
                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                    <a href="{{ route('students.show', $student) }}" class="text-slate-700">View</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('students.edit', $student) }}" class="text-slate-700">Edit</a>
                        <form method="POST" action="{{ route('students.destroy', $student) }}" data-confirm="Delete this student?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-600">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No students found.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Name</th>
                    <th class="px-6 py-3 font-medium">Program</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium">Phone</th>
                    <th class="px-6 py-3 font-medium">Email</th>
                    <th class="px-6 py-3 font-medium">Remaining</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($students as $student)
                    @php
                        $remainingSessions = $student->payments_sum_remaining_sessions ?? $student->getRemainingSessions();
                        $remainingClass = $remainingSessions <= 0
                            ? 'bg-rose-100 text-rose-700'
                            : ($remainingSessions <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
                    @endphp
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $student->name }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                {{ $student->programLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $student->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $student->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $student->phone }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $student->email ?: '-' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $remainingClass }}">
                                    {{ $remainingSessions }} sessions
                                </span>
                                @if ($student->getOutstandingPaymentDebt() > 0)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        Total Debt: {{ $student->getOutstandingPaymentDebtLabel() }}
                                    </span>
                                @endif
                                @if ($student->getTokenDebtCount() > 0)
                                    <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                                        Token Debt: {{ $student->getTokenDebtLabel() }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('students.show', $student) }}" class="text-sm font-medium text-slate-700">View</a>
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('students.edit', $student) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                    <form method="POST" action="{{ route('students.toggle-status', $student) }}" data-confirm="Change this student's active status?">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm font-medium {{ $student->is_active ? 'text-amber-600' : 'text-emerald-600' }}">
                                            {{ $student->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" data-confirm="Delete this student?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-500">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>
</x-app-layout>
