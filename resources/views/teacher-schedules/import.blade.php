<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <a href="{{ route('teacher-schedules.index') }}" class="text-sm text-slate-500">&larr; Kembali ke Jadwal Guru</a>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Import Jadwal Kelas (CSV)</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Unggah file</h3>
            <p class="mt-2 text-sm text-slate-600">
                Format kolom (baris pertama = header):
                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-800" style="font-family:monospace">nama_kelas, hari, jam_mulai, jam_selesai</span>.
            </p>
            <p class="mt-1 text-sm text-slate-600">
                Nama hari Bahasa Indonesia (Senin–Minggu). Jam format
                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-800" style="font-family:monospace">HH:MM</span> (24 jam).
                Satu kelas boleh punya beberapa baris untuk beberapa hari. Kelas yang belum ada dibuat otomatis
                (default English · Semi · Teens/Adult, bisa diubah lewat Edit kelas). Jadwal dibuat tanpa guru dulu — assign guru lewat Edit jadwal.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-medium">{{ $errors->first('file') }}</p>
                    @if (session('import_errors'))
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach (session('import_errors') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('teacher-schedules.import.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <x-input-label for="file" value="File CSV" />
                    <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required
                        class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="inline-flex justify-center rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                    Import
                </button>
            </form>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Contoh isi CSV</h3>
            <pre class="mt-3 overflow-x-auto whitespace-pre text-sm text-slate-700" style="font-family:monospace">nama_kelas,hari,jam_mulai,jam_selesai
Kelas A,Senin,17:00,18:30
Kelas A,Rabu,17:00,18:30
Kelas B,Selasa,14:00,15:30
Kelas B,Kamis,14:00,15:30</pre>
        </div>
    </div>
</x-app-layout>
