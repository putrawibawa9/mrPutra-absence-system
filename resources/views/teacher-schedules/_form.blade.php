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
        <x-input-label for="classroom_id" value="Kelas" />
        <select id="classroom_id" name="classroom_id" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="">Pilih kelas</option>
            @foreach ($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id', $schedule->classroom_id ?? '') == $classroom->id)>{{ $classroom->code ? $classroom->code.' · ' : '' }}{{ $classroom->nameWithStudentHint() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('classroom_id')" class="mt-2" />
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
        <p class="mt-1 text-xs text-slate-500">Jam selesai otomatis: jam mulai + 70 menit.</p>
        <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
        <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
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
