@extends('layouts.joki')

@section('title', $project->project_title . ' | Manajemen Joki Mr. Putra')

@section('content')
    @php
        $latestProgress = $project->latestProgress;
        $progressPercentage = $latestProgress?->progress_percentage ?? 0;
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Project Detail</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $project->project_title }}</h2>
            <p class="mt-2 text-sm text-slate-600">Client: {{ $project->client_name }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700">
                Edit Project
            </a>
            <a href="{{ route('projects.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700">
                Kembali
            </a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Informasi Project</h3>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Project Title</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $project->project_title }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Client Name</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $project->client_name }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Total Cost</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $project->formattedTotalCost() }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Latest Progress</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $progressPercentage }}%</dd>
                </div>
            </dl>

            <div class="mt-5">
                <div class="h-3 rounded-full bg-slate-200">
                    <div class="h-3 rounded-full bg-slate-900" style="width: {{ $progressPercentage }}%"></div>
                </div>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $latestProgress?->progress_note ?: 'Belum ada progress yang dicatat.' }}
                </p>
            </div>
        </div>

        <div id="add-progress" class="rounded-2xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Add Progress</h3>
            <p class="mt-1 text-sm text-slate-500">Tambahkan catatan perkembangan terbaru untuk proyek ini.</p>

            <form method="POST" action="{{ route('projects.progress.store', $project) }}" class="mt-5 space-y-5">
                @csrf

                <div>
                    <label for="progress_note" class="mb-2 block text-sm font-medium text-slate-700">Progress Note</label>
                    <textarea
                        id="progress_note"
                        name="progress_note"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-0"
                        placeholder="Contoh: Bab 1 dan Bab 2 sudah selesai dikerjakan."
                        required
                    >{{ old('progress_note') }}</textarea>
                </div>

                <div>
                    <label for="progress_percentage" class="mb-2 block text-sm font-medium text-slate-700">Progress Percentage</label>
                    <input
                        id="progress_percentage"
                        name="progress_percentage"
                        type="number"
                        min="0"
                        max="100"
                        value="{{ old('progress_percentage') }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-0"
                        placeholder="0 - 100"
                        required
                    >
                </div>

                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                    Simpan Progress
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Riwayat Progress</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Progress</th>
                        <th class="px-6 py-3 font-medium">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($project->progresses as $progress)
                        <tr>
                            <td class="px-6 py-4 text-slate-600">{{ $progress->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex min-w-40 items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full bg-slate-900" style="width: {{ $progress->progress_percentage }}%"></div>
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $progress->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $progress->progress_note }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-500">
                                Belum ada progress untuk proyek ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
