<x-guest-layout>
    <h2 class="text-lg font-semibold text-slate-900">Pendaftaran English Course</h2>
    <p class="mt-1 text-sm text-slate-500">Isi data di bawah ini. Bisa mendaftarkan lebih dari satu murid (mis. bareng teman). Tim Mr. Putra Speak akan menghubungi Anda lewat WhatsApp untuk penjadwalan.</p>

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

        @php($oldStudents = old('students', [['name' => '', 'age' => '']]))
        <div>
            <span class="block text-sm font-medium text-slate-700">Data Murid <span class="text-rose-500">*</span></span>
            <p class="text-xs text-slate-500">Tambahkan lebih dari satu bila mendaftar bersama teman.</p>

            <div id="student-rows" class="mt-2 space-y-2">
                @foreach ($oldStudents as $i => $s)
                    <div class="student-row flex items-end gap-2">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs text-slate-500">Nama Murid</label>
                            <input name="students[{{ $i }}][name]" type="text" required value="{{ $s['name'] ?? '' }}" placeholder="Nama lengkap" class="mt-1 block w-full rounded-xl border-slate-300">
                        </div>
                        <div class="w-24">
                            <label class="block text-xs text-slate-500">Umur</label>
                            <input name="students[{{ $i }}][age]" type="number" min="1" max="120" required value="{{ $s['age'] ?? '' }}" class="mt-1 block w-full rounded-xl border-slate-300">
                        </div>
                        <button type="button" class="student-remove rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600">Hapus</button>
                    </div>
                @endforeach
            </div>

            <button type="button" id="student-add" class="mt-2 inline-flex rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700">+ Tambah murid</button>

            <template id="student-template">
                <div class="student-row flex items-end gap-2">
                    <div class="min-w-0 flex-1">
                        <label class="block text-xs text-slate-500">Nama Murid</label>
                        <input name="students[__IDX__][name]" type="text" required placeholder="Nama lengkap" class="mt-1 block w-full rounded-xl border-slate-300">
                    </div>
                    <div class="w-24">
                        <label class="block text-xs text-slate-500">Umur</label>
                        <input name="students[__IDX__][age]" type="number" min="1" max="120" required class="mt-1 block w-full rounded-xl border-slate-300">
                    </div>
                    <button type="button" class="student-remove rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600">Hapus</button>
                </div>
            </template>
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700">No. WhatsApp <span class="text-rose-500">*</span></label>
            <input id="phone" name="phone" type="text" required value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" class="mt-1 block w-full rounded-xl border-slate-300">
            <p class="mt-1 text-xs text-slate-500">Satu nomor kontak untuk semua murid di atas.</p>
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

    <script>
        (function () {
            const wrap = document.getElementById('student-rows');
            const addBtn = document.getElementById('student-add');
            const tpl = document.getElementById('student-template');
            if (!wrap || !addBtn || !tpl) return;

            let idx = wrap.querySelectorAll('.student-row').length;

            addBtn.addEventListener('click', function () {
                const html = tpl.innerHTML.replace(/__IDX__/g, idx++);
                const frag = document.createElement('div');
                frag.innerHTML = html.trim();
                wrap.appendChild(frag.firstChild);
            });

            wrap.addEventListener('click', function (e) {
                if (!e.target.classList.contains('student-remove')) return;
                const rows = wrap.querySelectorAll('.student-row');
                const row = e.target.closest('.student-row');
                if (rows.length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input').forEach(el => el.value = '');
                }
            });
        })();
    </script>
</x-guest-layout>
