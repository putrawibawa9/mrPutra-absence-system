<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('name', $student->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="phone" value="Phone" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('phone', $student->phone ?? '')" required />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="email" value="Email (optional)" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('email', $student->email ?? '')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="registration_date" value="Registration Date" />
        <x-text-input id="registration_date" name="registration_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('registration_date', isset($student) ? optional($student->registration_date)->format('Y-m-d') : now()->toDateString())" required />
        <x-input-error :messages="$errors->get('registration_date')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="program_type" value="Program" />
        <select id="program_type" name="program_type" class="mt-1 block w-full rounded-xl border-slate-300" required>
            <option value="">Select program</option>
            @foreach (\App\Models\Student::programOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('program_type', $student->program_type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('program_type')" class="mt-2" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="book_info" value="Book Info (optional)" />
        <textarea id="book_info" name="book_info" rows="4" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Example: English File Elementary, Unit 4.">{{ old('book_info', $student->book_info ?? '') }}</textarea>
        <p class="mt-2 text-xs text-slate-500">Isi buku yang dipakai murid, level, unit, atau catatan buku lainnya.</p>
        <x-input-error :messages="$errors->get('book_info')" class="mt-2" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="is_active" value="Status" />
        <select id="is_active" name="is_active" class="mt-1 block w-full rounded-xl border-slate-300">
            <option value="1" @selected((string) old('is_active', isset($student) ? (int) $student->is_active : 1) === '1')>Active</option>
            <option value="0" @selected((string) old('is_active', isset($student) ? (int) $student->is_active : 1) === '0')>Inactive</option>
        </select>
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('students.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Cancel</a>
    <x-primary-button class="bg-slate-900 hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-950">
        Save Student
    </x-primary-button>
</div>
