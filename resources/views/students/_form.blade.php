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
