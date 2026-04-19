<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="teacher_id" value="Guru" />
        <select id="teacher_id" name="teacher_id" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="">Pilih guru</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('teacher_id', $schedule->teacher_id ?? '') == $teacher->id)>{{ $teacher->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="day_of_week" value="Hari" />
        <select id="day_of_week" name="day_of_week" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="">Pilih hari</option>
            @foreach ($dayOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('day_of_week', $schedule->day_of_week ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('day_of_week')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="start_time" value="Jam Mulai" />
        <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('start_time', isset($schedule) ? substr((string) $schedule->start_time, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_time" value="Jam Selesai" />
        <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('end_time', isset($schedule) ? substr((string) $schedule->end_time, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="student_id" value="Murid (opsional)" />
        <select id="student_id" name="student_id" class="mt-1 block w-full rounded-xl border-slate-300">
            <option value="">Tanpa murid spesifik</option>
            @foreach ($students as $student)
                <option value="{{ $student->id }}" @selected(old('student_id', $schedule->student_id ?? '') == $student->id)>{{ $student->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="title" value="Info Kelas / Course" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('title', $schedule->title ?? '')" placeholder="Contoh: Private English / Group Coding" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Catatan (opsional)" />
        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-2xl border-slate-300">{{ old('notes', $schedule->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="is_active" value="Status" />
        <select id="is_active" name="is_active" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="1" @selected(old('is_active', isset($schedule) ? (int) $schedule->is_active : 1) == 1)>Aktif</option>
            <option value="0" @selected(old('is_active', isset($schedule) ? (int) $schedule->is_active : 1) == 0)>Nonaktif</option>
        </select>
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('teacher-schedules.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Batal</a>
    <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950 sm:w-auto">
        Simpan Jadwal
    </x-primary-button>
</div>
