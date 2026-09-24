<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <a href="{{ route('my-leave.index') }}" class="text-sm text-slate-500">&larr; Kembali</a>
            <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Ajukan Libur</h2>
        </div>
    </x-slot>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('my-leave.store') }}" class="space-y-6">
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

            <div>
                <x-input-label for="date" value="Tanggal Libur" />
                <x-text-input id="date" name="date" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('date', now()->toDateString())" required />
                <x-input-error :messages="$errors->get('date')" class="mt-2" />
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="start_time" value="Jam Mulai (opsional)" />
                    <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('start_time')" />
                    <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="end_time" value="Jam Selesai (opsional)" />
                    <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('end_time')" />
                    <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                </div>
            </div>
            <p class="-mt-3 text-xs text-slate-500">Kosongkan jam untuk libur <span class="font-medium">sehari penuh</span>. Isi jam bila hanya sebagian hari.</p>

            <div>
                <x-input-label for="reason" value="Alasan (opsional)" />
                <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="mis. acara keluarga, sakit, dll.">{{ old('reason') }}</textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('my-leave.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600">Batal</a>
                <x-primary-button class="inline-flex w-full justify-center bg-slate-900 hover:bg-slate-800 sm:w-auto">Kirim Pengajuan</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
