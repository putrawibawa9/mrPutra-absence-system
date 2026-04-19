@extends('layouts.joki')

@section('title', 'Dashboard | Manajemen Joki Mr. Putra')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Dashboard</h2>
            <p class="text-sm text-slate-500">Ringkasan cepat jumlah proyek, total pemasukan, dan update progress terbaru.</p>
        </div>

        <a href="{{ route('projects.create') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Tambah Project
        </a>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total Projects</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $totalProjects }}</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total Revenue</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Latest Progress Updates</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Project</th>
                        <th class="px-6 py-3 font-medium">Client</th>
                        <th class="px-6 py-3 font-medium">Progress</th>
                        <th class="px-6 py-3 font-medium">Note</th>
                        <th class="px-6 py-3 font-medium">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($latestProgressUpdates as $progress)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <a href="{{ route('projects.show', $progress->project) }}" class="hover:text-slate-600">
                                    {{ $progress->project->project_title }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $progress->project->client_name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex min-w-40 items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full bg-slate-900" style="width: {{ $progress->progress_percentage }}%"></div>
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $progress->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $progress->progress_note }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $progress->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                Belum ada update progress. Tambahkan project dan progress pertama untuk mulai mengelola pesanan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
