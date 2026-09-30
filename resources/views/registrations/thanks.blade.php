<x-guest-layout>
    @php($names = $names ?? [])
    <div class="text-center">
        <h2 class="text-lg font-semibold text-slate-900">Terima kasih, {{ implode(' & ', $names) }}!</h2>
        <p class="mt-2 text-sm text-slate-600">
            @if (count($names) > 1)
                {{ count($names) }} pendaftaran ({{ implode(', ', $names) }}) sudah kami terima.
            @else
                Pendaftaran Anda sudah kami terima.
            @endif
            Tim Mr. Putra Speak akan segera menghubungi Anda lewat WhatsApp untuk penjadwalan les. Sampai jumpa!
        </p>
    </div>
</x-guest-layout>
