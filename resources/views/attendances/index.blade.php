<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Attendances</h2>
                <p class="text-sm text-slate-500">Every saved attendance consumes exactly one session from the selected payment.</p>
            </div>
            <a href="{{ route('attendances.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Attendance</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($attendances as $attendance)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $attendance->student->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $attendance->date->format('d M Y') }}</p>
                        @if ($attendance->batch)
                            <p class="mt-1 text-xs font-medium text-slate-500">{{ $attendance->batch->title }}</p>
                        @endif
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $attendance->payment->displayLabel() }}</span>
                </div>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Teacher</dt>
                        <dd class="font-medium text-slate-900">{{ $attendance->teacher->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Notes</dt>
                        <dd class="mt-1 text-slate-900">{{ $attendance->notes ?: '-' }}</dd>
                    </div>
                </dl>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No attendances recorded.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Date</th>
                    <th class="px-6 py-3 font-medium">Session</th>
                    <th class="px-6 py-3 font-medium">Student</th>
                    <th class="px-6 py-3 font-medium">Teacher</th>
                    <th class="px-6 py-3 font-medium">Package</th>
                    <th class="px-6 py-3 font-medium">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($attendances as $attendance)
                    <tr>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->batch?->title ?: 'Single Attendance' }}</td>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $attendance->student->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->teacher->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->payment->displayLabel() }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $attendance->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">No attendances recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $attendances->links() }}
    </div>
</x-app-layout>
