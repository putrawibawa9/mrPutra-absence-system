@php
    use App\Models\Classroom;
    $classroom = $classroom ?? null;
    $selectedStudentIds = collect(old('student_ids', $selectedStudentIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6"
    x-data="{
        division: @js(old('division', $classroom->division ?? '')),
        format: @js(old('format', $classroom->format ?? 'semi')),
        age: @js(old('age_group', $classroom->age_group ?? '')),
        name: @js(old('name', $classroom->name ?? '')),
        nameTouched: {{ old('name', $classroom->name ?? '') ? 'true' : 'false' }},
        q: '',
        labels: {
            division: @js(Classroom::divisionOptions()),
            format: @js(Classroom::formatOptions()),
            age: @js(Classroom::ageOptions()),
        },
        get autoName() {
            return [this.labels.division[this.division], this.labels.format[this.format], this.labels.age[this.age]].filter(Boolean).join(' · ');
        },
        syncName() { if (!this.nameTouched) this.name = this.autoName; },
    }">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-3">
        <div>
            <x-input-label value="Divisi" />
            <div class="mt-2 space-y-2">
                @foreach (Classroom::divisionOptions() as $value => $label)
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-sm" :class="division === '{{ $value }}' ? 'border-slate-900 bg-slate-50' : 'border-slate-200'">
                        <input type="radio" name="division" value="{{ $value }}" x-model="division" @change="syncName()"> {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <x-input-label value="Format" />
            <div class="mt-2 space-y-2">
                @foreach (Classroom::formatOptions() as $value => $label)
                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border p-3 text-sm" :class="format === '{{ $value }}' ? 'border-slate-900 bg-slate-50' : 'border-slate-200'">
                        <input type="radio" name="format" value="{{ $value }}" x-model="format" @change="syncName()" class="mt-0.5">
                        <span>
                            <span class="block font-medium">{{ $label }}</span>
                            <span class="block text-xs text-slate-500">{{ $value === 'private' ? '1 murid (sesi 1-on-1)' : 'Banyak murid (semi-private)' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <x-input-label value="Umur" />
            <div class="mt-2 space-y-2">
                @foreach (Classroom::ageOptions() as $value => $label)
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-sm" :class="age === '{{ $value }}' ? 'border-slate-900 bg-slate-50' : 'border-slate-200'">
                        <input type="radio" name="age_group" value="{{ $value }}" x-model="age" @change="syncName()"> {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="learning_mode" value="Mode Belajar (opsional, default otomatis)" />
            <select id="learning_mode" name="learning_mode" class="mt-1 block w-full rounded-xl border-slate-300">
                <option value="">— Otomatis dari Divisi + Format —</option>
                @foreach (Classroom::learningModeOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('learning_mode', $classroom->learning_mode ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">English · Semi = Synchronous (bolos hangus). Lainnya = Self-paced (token expiry).</p>
        </div>
        <div>
            <x-input-label for="level" value="Level (opsional)" />
            <select id="level" name="level" class="mt-1 block w-full rounded-xl border-slate-300">
                <option value="">—</option>
                @foreach (Classroom::levelOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('level', $classroom->level ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="name" value="Nama Kelas (otomatis, bisa diedit)" />
            <input id="name" name="name" type="text" x-model="name" @input="nameTouched = true" :placeholder="autoName || 'Pilih Divisi · Format · Umur'"
                class="mt-1 block w-full rounded-xl border-slate-300">
        </div>
        <div>
            <x-input-label value="Status" />
            <select name="is_active" class="mt-1 block w-full rounded-xl border-slate-300">
                <option value="1" @selected((int) old('is_active', $classroom->is_active ?? 1) === 1)>Aktif</option>
                <option value="0" @selected((int) old('is_active', $classroom->is_active ?? 1) === 0)>Nonaktif</option>
            </select>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between gap-3">
            <x-input-label value="Daftar Murid" />
            <span class="text-xs text-slate-500" x-text="format === 'private' ? 'Pilih 1 murid' : 'Pilih murid kelas'"></span>
        </div>
        <input type="text" x-model="q" placeholder="Cari nama / nomor HP..." class="mt-1 block w-full rounded-xl border-slate-300">
        <div class="mt-3 max-h-80 space-y-1 overflow-y-auto rounded-2xl border border-slate-200 p-2">
            @forelse ($students as $student)
                <label class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50"
                    x-show="q === '' || '{{ Str::lower($student->name) }}'.includes(q.toLowerCase()) || '{{ $student->phone }}'.includes(q)">
                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" @checked(in_array($student->id, $selectedStudentIds, true)) class="rounded">
                    <span class="text-sm text-slate-800">{{ $student->name }}</span>
                    <span class="text-xs text-slate-400">{{ $student->phone }}</span>
                </label>
            @empty
                <p class="px-3 py-2 text-sm text-slate-500">Belum ada murid aktif.</p>
            @endforelse
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="inline-flex justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white">{{ $submitLabel ?? 'Simpan' }}</button>
        <a href="{{ route('classrooms.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700">Batal</a>
    </div>
</form>
