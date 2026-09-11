<x-guest-layout>
    <h2 class="text-lg font-semibold text-slate-900">Pendaftaran Murid Baru</h2>
    <p class="mt-1 text-sm text-slate-500">Isi data di bawah ini. Tim Mr. Putra Speak akan menghubungi Anda untuk penjadwalan.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('registrations.store') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="student_name" class="block text-sm font-medium text-slate-700">Nama Murid <span class="text-rose-500">*</span></label>
            <input id="student_name" name="student_name" type="text" required value="{{ old('student_name') }}" class="mt-1 block w-full rounded-xl border-slate-300">
        </div>

        <div>
            <label for="guardian_name" class="block text-sm font-medium text-slate-700">Nama Orang Tua / Wali <span class="text-slate-400">(opsional)</span></label>
            <input id="guardian_name" name="guardian_name" type="text" value="{{ old('guardian_name') }}" class="mt-1 block w-full rounded-xl border-slate-300">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">No. WhatsApp <span class="text-rose-500">*</span></label>
                <input id="phone" name="phone" type="text" required value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" class="mt-1 block w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label for="age" class="block text-sm font-medium text-slate-700">Umur <span class="text-slate-400">(tahun)</span></label>
                <input id="age" name="age" type="number" min="1" max="120" value="{{ old('age') }}" class="mt-1 block w-full rounded-xl border-slate-300">
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email <span class="text-slate-400">(opsional)</span></label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-xl border-slate-300">
        </div>

        <div>
            <label for="program" class="block text-sm font-medium text-slate-700">Program yang Diminati <span class="text-rose-500">*</span></label>
            <select id="program" name="program" required class="mt-1 block w-full rounded-xl border-slate-300">
                <option value="">Pilih program</option>
                @foreach ($programOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('program') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="format_preference" class="block text-sm font-medium text-slate-700">Format Les</label>
            <select id="format_preference" name="format_preference" class="mt-1 block w-full rounded-xl border-slate-300">
                <option value="">Belum yakin</option>
                @foreach ($formatOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('format_preference') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="goal" class="block text-sm font-medium text-slate-700">Tujuan / Kebutuhan Les <span class="text-slate-400">(opsional)</span></label>
            <textarea id="goal" name="goal" rows="3" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Mis. persiapan IELTS, percakapan sehari-hari, ngoding dasar...">{{ old('goal') }}</textarea>
        </div>

        <div>
            <label for="level_note" class="block text-sm font-medium text-slate-700">Level / Pengalaman Saat Ini <span class="text-slate-400">(opsional)</span></label>
            <input id="level_note" name="level_note" type="text" value="{{ old('level_note') }}" placeholder="Mis. pemula, sudah pernah les 1 tahun..." class="mt-1 block w-full rounded-xl border-slate-300">
        </div>

        <div>
            <span class="block text-sm font-medium text-slate-700">Hari yang Bisa Les</span>
            <div class="mt-2 grid grid-cols-2 gap-2">
                @foreach ($dayOptions as $value => $label)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="available_days[]" value="{{ $value }}" @checked(in_array($value, old('available_days', [])))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <span class="block text-sm font-medium text-slate-700">Preferensi Waktu</span>
            <div class="mt-2 flex flex-wrap gap-4">
                @foreach ($timeOptions as $value => $label)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="time_preferences[]" value="{{ $value }}" @checked(in_array($value, old('time_preferences', [])))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label for="referral_source" class="block text-sm font-medium text-slate-700">Tahu Mr. Putra Speak dari mana? <span class="text-slate-400">(opsional)</span></label>
            <input id="referral_source" name="referral_source" type="text" value="{{ old('referral_source') }}" placeholder="Instagram, teman, Google..." class="mt-1 block w-full rounded-xl border-slate-300">
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-slate-700">Catatan Tambahan <span class="text-slate-400">(opsional)</span></label>
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">Kirim Pendaftaran</button>
    </form>
</x-guest-layout>
