<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kwitansi {{ $payment->displayReceiptNumber() }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 py-6 text-slate-900 sm:py-10">
        <div class="mx-auto max-w-xl px-4">
            @unless ($isPublicReceipt)
                <div class="mb-4 flex flex-wrap gap-2 print:hidden">
                    <a href="{{ route('payments.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">
                        Kembali
                    </a>
                    @if ($whatsAppShareUrl)
                        <a href="{{ $whatsAppShareUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center rounded-xl border border-emerald-200 bg-white px-4 py-2 text-sm font-medium text-emerald-700">
                            Kirim via WA
                        </a>
                    @endif
                    <button onclick="window.print()" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                        Cetak / Simpan PDF
                    </button>
                    <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Hapus pembayaran ini? Pembayaran dihapus dan absensi terkait menjadi utang token.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600">
                            Hapus
                        </button>
                    </form>
                </div>
            @endunless

            {{-- ===== Kwitansi (ringkas, tampil juga saat cetak & di halaman publik) ===== --}}
            <div class="rounded-[2rem] bg-white p-6 shadow-lg sm:p-10 print:rounded-none print:shadow-none">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Kwitansi Pembayaran</p>
                        <h1 class="mt-2 text-2xl font-semibold text-slate-900">Mr. Putra</h1>
                    </div>
                    <div class="text-sm sm:text-right">
                        <p class="text-slate-500">No. Kwitansi</p>
                        <p class="font-semibold text-slate-900">{{ $payment->displayReceiptNumber() }}</p>
                        <p class="mt-2 text-slate-500">Tanggal</p>
                        <p class="font-semibold text-slate-900">{{ $payment->payment_date->format('d M Y') }}</p>
                    </div>
                </div>

                <div class="mt-8" style="display:grid;grid-template-columns:150px 1fr;gap:0.75rem 1rem;align-items:start;">
                    <div class="text-sm text-slate-500">Diterima dari</div>
                    <div class="text-sm font-semibold text-slate-900">
                        {{ $payment->student->name }}
                        @if ($payment->student->phone)
                            <span class="font-normal text-slate-500">({{ $payment->student->phone }})</span>
                        @endif
                    </div>

                    <div class="text-sm text-slate-500">Jumlah</div>
                    <div class="text-lg font-semibold text-slate-900">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</div>

                    <div class="text-sm text-slate-500">Terbilang</div>
                    <div class="text-sm text-slate-600" style="font-style:italic;">
                        {{ \Illuminate\Support\Str::title(\App\Support\Terbilang::rupiah($payment->amount_paid)) }}
                    </div>

                    <div class="text-sm text-slate-500">Untuk pembayaran</div>
                    <div class="text-sm font-semibold text-slate-900">
                        {{ $payment->displayLabel() }}
                        @if ($payment->total_sessions > 0)
                            <span class="font-normal text-slate-500">— {{ $payment->total_sessions }} sesi/token</span>
                        @endif
                        @if ($payment->notes)
                            <span class="block font-normal text-slate-500">{{ $payment->notes }}</span>
                        @endif
                    </div>

                    @if ($payment->outstandingAmount() > 0)
                        <div class="text-sm text-slate-500">Sisa pembayaran</div>
                        <div class="text-sm font-semibold text-amber-700">Rp {{ number_format($payment->outstandingAmount(), 0, ',', '.') }}</div>
                    @endif
                </div>

                <div class="mt-10 flex justify-end border-t border-slate-200 pt-8">
                    <div class="text-center">
                        <p class="text-sm text-slate-600">Bali, {{ $payment->payment_date->format('d M Y') }}</p>
                        <p class="mt-1 text-sm text-slate-500">Hormat kami,</p>
                        <div class="mt-2 flex min-h-24 items-end justify-center">
                            @if ($payment->signatureUrl())
                                <img src="{{ $payment->signatureUrl() }}" alt="Tanda tangan" class="max-h-24 w-auto object-contain">
                            @endif
                        </div>
                        <p class="mt-2 font-semibold text-slate-900">{{ $payment->signer?->name ?? 'Mr. Putra' }}</p>
                    </div>
                </div>
            </div>

            @unless ($isPublicReceipt)
                {{-- Simpan link untuk bukti kwitansi (admin) --}}
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 print:hidden">
                    <p class="text-sm font-semibold text-slate-700">Link bukti kwitansi</p>
                    <p class="mt-1 text-xs text-slate-500">Simpan / bagikan link ini sebagai bukti kwitansi. Bisa dibuka tanpa login.</p>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <input id="receiptLink" type="text" readonly value="{{ $publicReceiptUrl }}" class="block w-full rounded-xl border-slate-300 text-sm text-slate-600">
                        <button type="button" onclick="copyReceiptLink()" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                            Salin Link
                        </button>
                    </div>
                </div>

                {{-- Kelola pembayaran (cicilan) — admin saja --}}
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 print:hidden">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Kelola Pembayaran</p>
                    <div class="mt-4 space-y-2 text-sm">
                        <p class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Tagihan</span>
                            <span class="font-medium text-slate-900">Rp {{ number_format($payment->price_amount, 0, ',', '.') }}</span>
                        </p>
                        <p class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Dibayar</span>
                            <span class="font-medium text-slate-900">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</span>
                        </p>
                        <p class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Sisa</span>
                            <span class="font-medium {{ $payment->outstandingAmount() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">Rp {{ number_format($payment->outstandingAmount(), 0, ',', '.') }}</span>
                        </p>
                        @php($studentNetTokens = $payment->student->getNetTokenBalance())
                        <p class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Saldo Token Murid</span>
                            <span class="font-medium {{ $studentNetTokens < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $studentNetTokens }} token</span>
                        </p>
                    </div>

                    @if ($payment->remaining_sessions > 0 && $payment->student->getTokenDebtCount() > 0)
                        <form method="POST" action="{{ route('payments.reconcile-debt', $payment) }}" class="mt-4" data-confirm="Lunasi utang token murid ke pembayaran ini sekarang?">
                            @csrf
                            <button type="submit" class="inline-flex justify-center rounded-xl border border-amber-200 bg-white px-4 py-2 text-sm font-medium text-amber-700">
                                Lunasi Utang Token
                            </button>
                        </form>
                    @endif

                    @if ($payment->outstandingAmount() > 0)
                        <form method="POST" action="{{ route('payments.installments.store', $payment) }}" class="mt-6 space-y-4" data-confirm="Simpan cicilan ini? Cek dulu nominalnya.">
                            @csrf
                            <div>
                                <label for="amount" class="text-sm font-medium text-slate-700">Tambah Cicilan</label>
                                <input id="amount" name="amount" type="number" min="1" max="{{ $payment->outstandingAmount() }}" value="{{ old('amount') }}" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Nominal dibayar">
                                @error('amount')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="payment_date" class="text-sm font-medium text-slate-700">Tanggal Bayar</label>
                                <input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="mt-1 block w-full rounded-xl border-slate-300">
                                @error('payment_date')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="installment_notes" class="text-sm font-medium text-slate-700">Catatan (opsional)</label>
                                <textarea id="installment_notes" name="notes" rows="2" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                                Simpan Cicilan
                            </button>
                        </form>
                    @endif

                    <div class="mt-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Riwayat Cicilan</p>
                        <div class="mt-3 space-y-2">
                            @forelse ($payment->installments as $installment)
                                <div class="rounded-xl bg-slate-50 p-4 text-sm">
                                    <p class="font-medium text-slate-900">Rp {{ number_format($installment->amount, 0, ',', '.') }}</p>
                                    <p class="mt-1 text-slate-500">{{ $installment->payment_date->format('d M Y') }} oleh {{ $installment->receiver?->name ?? '-' }}</p>
                                    @if ($installment->notes)
                                        <p class="mt-1 text-slate-600">{{ $installment->notes }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">Belum ada cicilan tercatat.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <script>
                    function copyReceiptLink() {
                        var input = document.getElementById('receiptLink');
                        input.select();
                        input.setSelectionRange(0, 99999);
                        navigator.clipboard.writeText(input.value).then(function () {
                            var btn = event.target;
                            var original = btn.textContent;
                            btn.textContent = 'Tersalin!';
                            setTimeout(function () { btn.textContent = original; }, 1500);
                        });
                    }
                </script>
            @endunless
        </div>
    </body>
</html>
