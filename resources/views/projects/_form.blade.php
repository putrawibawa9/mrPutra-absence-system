@php
    $project = $project ?? null;
@endphp

<div class="grid gap-5">
    <div>
        <label for="project_title" class="mb-2 block text-sm font-medium text-slate-700">Project Title</label>
        <input
            id="project_title"
            name="project_title"
            type="text"
            value="{{ old('project_title', $project?->project_title) }}"
            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-0"
            placeholder="Contoh: Sistem Informasi Akademik"
            required
        >
    </div>

    <div>
        <label for="client_name" class="mb-2 block text-sm font-medium text-slate-700">Client Name</label>
        <input
            id="client_name"
            name="client_name"
            type="text"
            value="{{ old('client_name', $project?->client_name) }}"
            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-0"
            placeholder="Nama mahasiswa"
            required
        >
    </div>

    <div>
        <label for="total_cost" class="mb-2 block text-sm font-medium text-slate-700">Total Cost</label>
        <input
            id="total_cost"
            name="total_cost"
            type="number"
            min="0"
            value="{{ old('total_cost', $project?->total_cost) }}"
            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-0"
            placeholder="Contoh: 2500000"
            required
        >
        <p class="mt-2 text-xs text-slate-500">Masukkan nominal tanpa titik atau koma.</p>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('projects.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700">
        Batal
    </a>
</div>
