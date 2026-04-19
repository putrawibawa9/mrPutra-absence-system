<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Teachers</h2>
                <p class="text-sm text-slate-500">Manage teacher accounts used in attendance records.</p>
            </div>
            <a href="{{ route('teachers.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Teacher</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($teachers as $teacher)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">{{ $teacher->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $teacher->email }}</p>
                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                    <a href="{{ route('teachers.edit', $teacher) }}" class="text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" data-confirm="Delete this teacher?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No teachers found.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Name</th>
                    <th class="px-6 py-3 font-medium">Email</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($teachers as $teacher)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $teacher->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $teacher->email }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('teachers.edit', $teacher) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" data-confirm="Delete this teacher?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-slate-500">No teachers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $teachers->links() }}
    </div>
</x-app-layout>
