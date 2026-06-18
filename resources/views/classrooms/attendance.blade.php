<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <a href="{{ route('classrooms.index') }}" class="text-sm text-slate-500">&larr; Kembali ke Kelas</a>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Absen Kelas — {{ $classroom->name }}</h2>
            <p class="text-sm text-slate-500">{{ $classroom->divisionLabel() }} · {{ $classroom->formatLabel() }} · {{ $classroom->ageLabel() }} — centang murid yang hadir, token mereka otomatis terpotong.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('classrooms.attendances.store', $classroom) }}" class="space-y-6">
        @csrf

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <x-input-label for="date" value="Tanggal" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('date', now()->toDateString())" />
                </div>
                <div>
                    <x-input-label for="teaching_minutes" value="Durasi (menit)" />
                    <x-text-input id="teaching_minutes" name="teaching_minutes" type="number" min="0" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('teaching_minutes', 60)" />
                </div>
                <div>
                    <x-input-label value="Guru" />
                    <div class="mt-1 max-h-28 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-2">
                        @foreach ($teachers as $teacher)
                            <label class="flex items-center gap-2 rounded-lg px-2 py-1 text-sm hover:bg-slate-50">
                                <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}"
                                    @checked(collect(old('teacher_ids', [auth()->id()]))->map(fn ($id) => (int) $id)->contains($teacher->id))>
                                {{ $teacher->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($materialLinks->isNotEmpty())
                <div class="mt-5">
                    <x-input-label value="Link Materi (opsional)" />
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($materialLinks as $materialLink)
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-1.5 text-sm">
                                <input type="checkbox" name="material_link_ids[]" value="{{ $materialLink->id }}"
                                    @checked(collect(old('material_link_ids', []))->map(fn ($id) => (int) $id)->contains($materialLink->id))>
                                {{ $materialLink->title }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-5">
                <x-input-label for="learning_journal" value="Catatan Belajar (opsional)" />
                <textarea id="learning_journal" name="learning_journal" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('learning_journal') }}</textarea>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-slate-900">Daftar Hadir</h3>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $classroom->students->count() }} murid</span>
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($classroom->students as $student)
                    @php($remaining = (int) $student->payments->sum('remaining_sessions'))
                    <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="present_student_ids[]" value="{{ $student->id }}"
                                @checked(collect(old('present_student_ids', $classroom->students->pluck('id')->all()))->map(fn ($id) => (int) $id)->contains($student->id))
                                class="h-5 w-5 rounded">
                            <span class="font-medium text-slate-900">{{ $student->name }}</span>
                        </div>
                        <span class="text-sm {{ $remaining > 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $remaining > 0 ? $remaining.' token tersisa' : 'Tidak ada token → token debt' }}
                        </span>
                    </label>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">Kelas ini belum punya murid. Tambahkan dulu lewat Edit Kelas.</p>
                @endforelse
            </div>

            @if ($classroom->students->isNotEmpty())
                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="inline-flex justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white">Simpan Absensi</button>
                    <a href="{{ route('classrooms.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700">Batal</a>
                </div>
            @endif
        </div>
    </form>
</x-app-layout>
