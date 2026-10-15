<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Cara Bayar - {{ $student->name }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 py-6 text-slate-900 sm:py-10">
        <div class="mx-auto max-w-md px-4">
            <div class="rounded-[2rem] bg-white p-6 shadow-lg sm:p-8">
                <div class="border-b border-slate-200 pb-5 text-center">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Cara Bayar</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Mr. Putra</h1>
                    <p class="mt-1 text-sm text-slate-500">Les untuk Ananda <span class="font-medium text-slate-700">{{ $student->name }}</span></p>
                </div>

                <div class="mt-6 rounded-2xl bg-slate-50 p-5 text-center">
                    <p class="text-sm text-slate-500">Jumlah yang perlu dibayar</p>
                    @if ($amount !== null)
                        <p class="mt-1 text-3xl font-semibold text-slate-900">Rp {{ number_format($amount, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-500">untuk {{ $sessions }} kali pertemuan</p>
                    @else
                        <p class="mt-1 text-lg font-semibold text-slate-700">Hubungi admin untuk nominal</p>
                    @endif
                </div>

                <div class="mt-6">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Scan QRIS</h2>
                    <div class="mt-3 flex flex-col items-center rounded-2xl border border-slate-200 p-5">
                        @if ($qrisUrl)
                            <img src="{{ $qrisUrl }}" alt="QRIS Mr. Putra" class="w-full max-w-xs rounded-xl">
                            <p class="mt-3 text-center text-xs text-slate-500">Scan dengan aplikasi e-wallet / m-banking (GoPay, OVO, DANA, BCA, dll). Bisa juga simpan gambar lalu pilih "scan dari galeri".</p>
                        @else
                            <p class="text-center text-sm text-slate-500">QRIS belum tersedia. Silakan gunakan transfer bank di bawah.</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Transfer Bank</h2>
                    <p class="mt-1 text-xs text-slate-500">a.n. I Gede Putra Wibawa</p>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                            <span class="text-sm font-medium text-slate-700">Bank Jago</span>
                            <span class="font-semibold text-slate-900">10180276877</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                            <span class="text-sm font-medium text-slate-700">BRI</span>
                            <span class="font-semibold text-slate-900">081501035456538</span>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-center text-sm text-slate-500">Setelah membayar, mohon konfirmasi bukti transfernya ke admin ya. Terima kasih banyak. 🙏</p>
            </div>
        </div>
    </body>
</html>
