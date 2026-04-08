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
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
                <a href="{{ route('payments.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">
                    Back to Payments
                </a>
                <button onclick="window.print()" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                    Print Receipt
                </button>
            </div>

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
                        <p class="mt-3 font-semibold text-slate-900">{{ $payment->signer?->name ?? auth()->user()->name }}</p>
                        <p class="text-sm text-slate-500">Authorized by Mr. Putra Absence System</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
