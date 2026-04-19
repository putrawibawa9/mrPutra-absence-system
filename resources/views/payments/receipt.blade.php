<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $payment->displayReceiptNumber() }} - E-Receipt</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 py-6 text-slate-900 sm:py-10">
        <div class="mx-auto max-w-4xl px-4">
            @unless ($isPublicReceipt)
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ route('payments.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">
                        Back to Payments
                    </a>
                    @if ($whatsAppShareUrl)
                        <a href="{{ $whatsAppShareUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center rounded-xl border border-emerald-200 bg-white px-4 py-2 text-sm font-medium text-emerald-700">
                            Send Receipt
                        </a>
                    @else
                        <span class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-400">
                            Send Receipt Unavailable
                        </span>
                    @endif
                    <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Delete this payment? The payment will be removed and linked attendances will become token debt.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600">
                            Delete Payment
                        </button>
                    </form>
                    @if ($payment->remaining_sessions > 0 && $payment->student->getTokenDebtCount() > 0)
                        <form method="POST" action="{{ route('payments.reconcile-debt', $payment) }}" data-confirm="Reconcile student debt to this payment now?">
                            @csrf
                            <button type="submit" class="inline-flex justify-center rounded-xl border border-amber-200 bg-white px-4 py-2 text-sm font-medium text-amber-700">
                                Reconcile Debt
                            </button>
                        </form>
                    @endif
                </div>
                <button onclick="window.print()" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                    Print Receipt
                </button>
                </div>
            @endunless

            <div class="rounded-[2rem] bg-white p-6 shadow-lg sm:p-10 print:rounded-none print:shadow-none">
                <div class="flex flex-col gap-6 border-b border-slate-200 pb-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-500">E-Receipt</p>
                        <h1 class="mt-3 text-3xl font-semibold text-slate-900">Mr. Putra Absence System</h1>
                        <p class="mt-2 text-sm text-slate-500">Student attendance and payment system</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-5 py-4 text-sm">
                        <p class="text-slate-500">Receipt Number</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $payment->displayReceiptNumber() }}</p>
                        <p class="mt-3 text-slate-500">Payment Date</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $payment->payment_date->format('d M Y') }}</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-8 lg:grid-cols-2">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Received From</h2>
                        <div class="mt-4 rounded-2xl border border-slate-200 p-5">
                            <p class="text-lg font-semibold text-slate-900">{{ $payment->student->name }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $payment->student->phone }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $payment->student->email ?: '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Payment Details</h2>
                        <div class="mt-4 rounded-2xl border border-slate-200 p-5">
                            <dl class="grid gap-3 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">Source</dt>
                                    <dd class="font-medium text-slate-900">{{ $payment->displayLabel() }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">Total Sessions</dt>
                                    <dd class="font-medium text-slate-900">{{ $payment->total_sessions }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">Remaining Sessions</dt>
                                    <dd class="font-medium text-slate-900">{{ $payment->remaining_sessions }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">Bill Amount</dt>
                                    <dd class="font-medium text-slate-900">Rp {{ number_format($payment->price_amount, 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">Amount Paid</dt>
                                    <dd class="font-medium text-slate-900">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500">Outstanding</dt>
                                    <dd class="font-medium {{ $payment->outstandingAmount() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">Rp {{ number_format($payment->outstandingAmount(), 0, ',', '.') }}</dd>
                                </div>
                                @if ($payment->notes)
                                    <div class="pt-2">
                                        <dt class="text-slate-500">Notes</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ $payment->notes }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_1.2fr] print:hidden">
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Payment Status</h2>
                        <div class="mt-4 space-y-3 text-sm">
                            <p class="flex items-center justify-between gap-4">
                                <span class="text-slate-500">Bill</span>
                                <span class="font-medium text-slate-900">Rp {{ number_format($payment->price_amount, 0, ',', '.') }}</span>
                            </p>
                            <p class="flex items-center justify-between gap-4">
                                <span class="text-slate-500">Paid</span>
                                <span class="font-medium text-slate-900">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</span>
                            </p>
                            <p class="flex items-center justify-between gap-4">
                                <span class="text-slate-500">Outstanding</span>
                                <span class="font-medium {{ $payment->outstandingAmount() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">Rp {{ number_format($payment->outstandingAmount(), 0, ',', '.') }}</span>
                            </p>
                            @if ($payment->remaining_sessions > 0)
                                <p class="flex items-center justify-between gap-4">
                                    <span class="text-slate-500">Student Debt</span>
                                    <span class="font-medium {{ $payment->student->getTokenDebtCount() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $payment->student->getTokenDebtCount() }}</span>
                                </p>
                            @endif
                        </div>

                        @if ($payment->outstandingAmount() > 0)
                            <form method="POST" action="{{ route('payments.installments.store', $payment) }}" class="mt-6 space-y-4" data-confirm="Save this installment payment? Please double check the nominal first.">
                                @csrf
                                <div>
                                    <label for="amount" class="text-sm font-medium text-slate-700">Add Installment</label>
                                    <input id="amount" name="amount" type="number" min="1" max="{{ $payment->outstandingAmount() }}" value="{{ old('amount') }}" class="mt-1 block w-full rounded-xl border-slate-300" placeholder="Nominal dibayar">
                                    @error('amount')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="payment_date" class="text-sm font-medium text-slate-700">Payment Date</label>
                                    <input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="mt-1 block w-full rounded-xl border-slate-300">
                                    @error('payment_date')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="installment_notes" class="text-sm font-medium text-slate-700">Notes (optional)</label>
                                    <textarea id="installment_notes" name="notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                                    Save Installment
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Installment History</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($payment->installments as $installment)
                                <div class="rounded-xl bg-slate-50 p-4 text-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-slate-900">Rp {{ number_format($installment->amount, 0, ',', '.') }}</p>
                                            <p class="mt-1 text-slate-500">{{ $installment->payment_date->format('d M Y') }} by {{ $installment->receiver?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                    @if ($installment->notes)
                                        <p class="mt-2 text-slate-600">{{ $installment->notes }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No installment recorded yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-10 grid gap-8 border-t border-slate-200 pt-8 sm:grid-cols-2">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Statement</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Receipt issued for student session payment and token balance record in Mr. Putra Absence System.
                        </p>
                    </div>

                    <div class="sm:text-right">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Authorized Signature</h2>
                        <div class="mt-4 flex min-h-28 items-end justify-start sm:justify-end">
                            @if ($payment->signatureUrl())
                                <img src="{{ $payment->signatureUrl() }}" alt="Signature" class="max-h-24 w-auto object-contain">
                            @else
                                <div class="h-24 w-48 rounded-2xl border border-dashed border-slate-300 bg-slate-50"></div>
                            @endif
                        </div>
                        <p class="mt-3 font-semibold text-slate-900">{{ $payment->signer?->name ?? config('app.name') }}</p>
                        <p class="text-sm text-slate-500">Authorized by Mr. Putra Absence System</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
