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

    <div class="space-y-4 md:hidden">
        @forelse ($students as $student)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-slate-900">{{ $student->name }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $student->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $student->statusLabel() }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $student->phone }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $student->email ?: '-' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $student->getRemainingSessions() > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $student->getRemainingSessions() }}
                    </span>
                </div>
                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                    <a href="{{ route('students.show', $student) }}" class="text-slate-700">View</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('students.edit', $student) }}" class="text-slate-700">Edit</a>
                        <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Delete this student?');">
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
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium">Phone</th>
                    <th class="px-6 py-3 font-medium">Email</th>
                    <th class="px-6 py-3 font-medium">Remaining</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($students as $student)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $student->name }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $student->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $student->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $student->phone }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $student->email ?: '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $student->getRemainingSessions() > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $student->getRemainingSessions() }} sessions
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('students.show', $student) }}" class="text-sm font-medium text-slate-700">View</a>
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('students.edit', $student) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                    <form method="POST" action="{{ route('students.toggle-status', $student) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm font-medium {{ $student->is_active ? 'text-amber-600' : 'text-emerald-600' }}">
                                            {{ $student->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Delete this student?');">
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
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>
</x-app-layout>
