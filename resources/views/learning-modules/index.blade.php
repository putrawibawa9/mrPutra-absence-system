<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Modules</h2>
                <p class="text-sm text-slate-500">Simpan daftar modul agar pembayaran modul lebih konsisten dan gampang dipantau di cash flow.</p>
            </div>
            <a href="{{ route('learning-modules.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Module</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($modules as $module)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $module->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Rp {{ number_format($module->price, 0, ',', '.') }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $module->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $module->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if ($module->notes)
                    <p class="mt-4 text-sm text-slate-600">{{ $module->notes }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('learning-modules.edit', $module) }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('learning-modules.destroy', $module) }}" data-confirm="Delete this module? Please make sure it is no longer needed in new payments.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No modules added yet.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Module</th>
                    <th class="px-6 py-3 font-medium">Price</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium">Notes</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($modules as $module)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $module->name }}</td>
                        <td class="px-6 py-4 text-slate-600">Rp {{ number_format($module->price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $module->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $module->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $module->notes ?: '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('learning-modules.edit', $module) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('learning-modules.destroy', $module) }}" data-confirm="Delete this module? Please make sure it is no longer needed in new payments.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No modules added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $modules->links() }}
    </div>
</x-app-layout>
