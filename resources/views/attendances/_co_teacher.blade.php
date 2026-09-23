@php($coTeacherRows = $coTeacherRows ?? [])
<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-slate-900">Co-teacher / Guru Training (opsional)</p>
            <p class="text-xs text-slate-500">Guru pendamping yang ikut mengajar. Isi salary custom-nya (Rp). Kosongkan salary bila belum ditentukan — bisa diisi nanti lewat Edit.</p>
        </div>
    </div>

    <div id="co-teacher-rows" class="mt-3 space-y-2">
        @forelse ($coTeacherRows as $row)
            <div class="co-teacher-row flex flex-wrap items-center gap-2">
                <select name="co_teacher_id[]" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm">
                    <option value="">— pilih guru —</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected((int) ($row['id'] ?? 0) === (int) $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="co_teacher_fee[]" min="0" value="{{ $row['fee'] ?? '' }}" placeholder="Salary (Rp)" class="w-48 rounded-xl border-slate-300 text-sm" />
                <button type="button" class="co-teacher-remove inline-flex justify-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600">Hapus</button>
            </div>
        @empty
            <div class="co-teacher-row flex flex-wrap items-center gap-2">
                <select name="co_teacher_id[]" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm">
                    <option value="">— pilih guru —</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="co_teacher_fee[]" min="0" value="" placeholder="Salary (Rp)" class="w-48 rounded-xl border-slate-300 text-sm" />
                <button type="button" class="co-teacher-remove inline-flex justify-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600">Hapus</button>
            </div>
        @endforelse
    </div>

    <button type="button" id="co-teacher-add" class="mt-3 inline-flex justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700">+ Tambah co-teacher</button>

    <template id="co-teacher-template">
        <div class="co-teacher-row flex flex-wrap items-center gap-2">
            <select name="co_teacher_id[]" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm">
                <option value="">— pilih guru —</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
            <input type="number" name="co_teacher_fee[]" min="0" value="" placeholder="Salary (Rp)" class="w-48 rounded-xl border-slate-300 text-sm" />
            <button type="button" class="co-teacher-remove inline-flex justify-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600">Hapus</button>
        </div>
    </template>
</div>

<script>
    (function () {
        const wrap = document.getElementById('co-teacher-rows');
        const addBtn = document.getElementById('co-teacher-add');
        const tpl = document.getElementById('co-teacher-template');
        if (!wrap || !addBtn || !tpl) return;

        addBtn.addEventListener('click', function () {
            wrap.appendChild(tpl.content.cloneNode(true));
        });

        wrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('co-teacher-remove')) {
                const rows = wrap.querySelectorAll('.co-teacher-row');
                const row = e.target.closest('.co-teacher-row');
                if (rows.length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('select, input').forEach(el => el.value = '');
                }
            }
        });
    })();
</script>
