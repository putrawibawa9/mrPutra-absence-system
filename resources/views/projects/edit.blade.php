@extends('layouts.joki')

@section('title', 'Edit Project | Manajemen Joki Mr. Putra')

@section('content')
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-slate-900">Edit Project</h2>
            <p class="text-sm text-slate-500">Perbarui informasi proyek sesuai kondisi terbaru.</p>
        </div>

        <form method="POST" action="{{ route('projects.update', $project) }}">
            @csrf
            @method('PUT')
            @include('projects._form', ['submitLabel' => 'Update Project', 'project' => $project])
        </form>
    </div>
@endsection
