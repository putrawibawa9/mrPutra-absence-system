<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Dashboard</h2>
                <p class="text-sm text-slate-500">Quick overview of students, active tokens, and follow-up payments.</p>
            </div>
            <a href="{{ route('attendances.create') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Add Attendance
            </a>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total students</p>
            <p class="mt-3 text-4xl font-semibold text-slate-900">{{ $totalStudents }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Active students</p>
            <p class="mt-3 text-4xl font-semibold text-emerald-600">{{ $activeStudents }}</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Need payment</p>
            <p class="mt-3 text-4xl font-semibold text-rose-600">{{ $inactiveStudents }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-3xl bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Students with 0 sessions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Student</th>
                        <th class="px-6 py-3 font-medium">Phone</th>
                        <th class="px-6 py-3 font-medium">Email</th>
                        <th class="px-6 py-3 font-medium">Remaining</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($studentsNeedingPayment as $student)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $student->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->phone }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $student->email ?: '-' }}</td>
                            <td class="px-6 py-4 text-rose-600">{{ $student->getRemainingSessions() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">All students still have available sessions.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
