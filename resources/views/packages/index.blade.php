<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Packages</h2>
                <p class="text-sm text-slate-500">Configure session packages and pricing.</p>
            </div>
            <a href="{{ route('packages.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Package</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($packages as $package)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">{{ $package->name }}</h3>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Sessions</dt>
                        <dd class="font-medium text-slate-900">{{ $package->total_sessions }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Price</dt>
                        <dd class="font-medium text-slate-900">Rp {{ number_format($package->price, 0, ',', '.') }}</dd>
                    </div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                    <a href="{{ route('packages.edit', $package) }}" class="text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('packages.destroy', $package) }}" onsubmit="return confirm('Delete this package?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No packages available.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Name</th>
                    <th class="px-6 py-3 font-medium">Sessions</th>
                    <th class="px-6 py-3 font-medium">Price</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($packages as $package)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $package->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $package->total_sessions }}</td>
                        <td class="px-6 py-4 text-slate-600">Rp {{ number_format($package->price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('packages.edit', $package) }}" class="text-sm font-medium text-slate-700">Edit</a>
                                <form method="POST" action="{{ route('packages.destroy', $package) }}" onsubmit="return confirm('Delete this package?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">No packages available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $packages->links() }}
    </div>
</x-app-layout>
