<x-guest-layout>
    <div class="text-center">
        <h2 class="text-lg font-semibold text-slate-900">Terima kasih, {{ $student->name }}!</h2>
        <p class="mt-2 text-sm text-slate-600">
            @if ($already)
                Kami mencatat Anda sudah pernah mengisi feedback. Terima kasih banyak atas masukannya!
            @else
                Feedback Anda sudah kami terima. Masukan Anda sangat berarti untuk perkembangan Mr. Putra Speak.
            @endif
        </p>
    </div>
</x-guest-layout>
