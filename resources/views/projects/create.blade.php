@extends('layouts.joki')

@section('title', 'Add Project | Manajemen Joki Mr. Putra')

@section('content')
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-slate-900">Add Project</h2>
            <p class="text-sm text-slate-500">Isi data proyek baru untuk mulai memantau pengerjaannya.</p>
        </div>

        <form method="POST" action="{{ route('projects.store') }}">
            @csrf
            @include('projects._form', ['submitLabel' => 'Simpan Project'])
        </form>
    </div>
@endsection
