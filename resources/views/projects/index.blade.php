@extends('layouts.joki')

@section('title', 'Projects | Manajemen Joki Mr. Putra')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Project Management</h2>
            <p class="text-sm text-slate-500">Daftar seluruh proyek joki beserta progres terbarunya.</p>
        </div>

        <a href="{{ route('projects.create') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Add Project
        </a>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Title</th>
                        <th class="px-6 py-3 font-medium">Client Name</th>
                        <th class="px-6 py-3 font-medium">Cost</th>
                        <th class="px-6 py-3 font-medium">Latest Progress</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($projects as $project)
                        @php
                            $latestProgress = $project->latestProgress;
                            $progressPercentage = $latestProgress?->progress_percentage ?? 0;
                        @endphp

                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $project->project_title }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $project->client_name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $project->formattedTotalCost() }}</td>
                            <td class="px-6 py-4">
                                <div class="flex min-w-40 items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full bg-slate-900" style="width: {{ $progressPercentage }}%"></div>
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $progressPercentage }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('projects.show', $project) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">
                                        View Detail
                                    </a>
                                    <a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('projects.destroy', $project) }}" data-confirm="Hapus proyek ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                Belum ada proyek. Klik tombol "Add Project" untuk menambahkan data pertama.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $projects->links() }}
    </div>
@endsection
