<x-guest-layout>
    <h2 class="text-lg font-semibold text-slate-900">Feedback untuk Mr. Putra Speak</h2>
    <p class="mt-1 text-sm text-slate-500">Halo {{ $student->name }}, terima kasih sudah belajar bersama kami. Masukan Anda sangat berarti.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <span class="block text-sm font-medium text-slate-700">Rating <span class="text-slate-400">(1 = kurang, 5 = sangat baik)</span></span>
            <div class="mt-2 flex flex-wrap gap-4">
                @for ($i = 1; $i <= 5; $i++)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="rating" value="{{ $i }}" @checked(old('rating') == $i) required>
                        {{ $i }}
                    </label>
                @endfor
            </div>
        </div>

        <div>
            <label for="message" class="block text-sm font-medium text-slate-700">Pesan &amp; Kesan</label>
            <textarea id="message" name="message" rows="4" required class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Bagaimana pengalaman belajar Anda?">{{ old('message') }}</textarea>
        </div>

        <div>
            <label for="suggestion" class="block text-sm font-medium text-slate-700">Saran <span class="text-slate-400">(opsional)</span></label>
            <textarea id="suggestion" name="suggestion" rows="3" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Ada yang bisa kami tingkatkan?">{{ old('suggestion') }}</textarea>
        </div>

        <label class="flex items-start gap-2 text-sm text-slate-600">
            <input type="checkbox" name="allow_testimonial" value="1" class="mt-1" @checked(old('allow_testimonial'))>
            Boleh menampilkan masukan ini sebagai testimoni.
        </label>

        <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">Kirim Feedback</button>
    </form>
</x-guest-layout>
