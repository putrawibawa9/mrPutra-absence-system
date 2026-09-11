<x-guest-layout>
    <h2 class="text-lg font-semibold text-slate-900">Pendaftaran English Course</h2>
    <p class="mt-1 text-sm text-slate-500">Isi data di bawah ini. Tim Mr. Putra Speak akan menghubungi Anda lewat WhatsApp untuk penjadwalan.</p>

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

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">No. WhatsApp <span class="text-rose-500">*</span></label>
                <input id="phone" name="phone" type="text" required value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" class="mt-1 block w-full rounded-xl border-slate-300">
            </div>
            <div>
                <label for="age" class="block text-sm font-medium text-slate-700">Umur (tahun) <span class="text-rose-500">*</span></label>
                <input id="age" name="age" type="number" min="1" max="120" required value="{{ old('age') }}" class="mt-1 block w-full rounded-xl border-slate-300">
            </div>
        </div>

        <div>
            <label for="format_preference" class="block text-sm font-medium text-slate-700">Format Les <span class="text-rose-500">*</span></label>
            <select id="format_preference" name="format_preference" required class="mt-1 block w-full rounded-xl border-slate-300">
                <option value="">Pilih format</option>
                @foreach ($formatOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('format_preference') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="goal" class="block text-sm font-medium text-slate-700">Tujuan Les <span class="text-rose-500">*</span></label>
            <select id="goal" name="goal" required class="mt-1 block w-full rounded-xl border-slate-300">
                <option value="">Pilih tujuan</option>
                @foreach ($goalOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('goal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <span class="block text-sm font-medium text-slate-700">Hari yang Bisa Les <span class="text-rose-500">*</span></span>
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
            <span class="block text-sm font-medium text-slate-700">Preferensi Jam <span class="text-rose-500">*</span></span>
            <div class="mt-2 flex flex-wrap gap-4">
                @foreach ($timeOptions as $value => $label)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="time_preferences[]" value="{{ $value }}" @checked(in_array($value, old('time_preferences', [])))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">Kirim Pendaftaran</button>
    </form>
</x-guest-layout>
