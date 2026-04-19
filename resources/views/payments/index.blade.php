<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Payments</h2>
                <p class="text-sm text-slate-500">Every payment creates tokens and automatically settles existing token debt first.</p>
            </div>
            <a href="{{ route('payments.create') }}" class="inline-flex justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Add Payment</a>
        </div>
    </x-slot>

    <div class="space-y-4 md:hidden">
        @forelse ($payments as $payment)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $payment->student->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $payment->displayLabel() }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $payment->remaining_sessions }} left</span>
                </div>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Receipt</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->displayReceiptNumber() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Total</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->total_sessions }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Payment date</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->payment_date->format('d M Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Bill</dt>
                        <dd class="font-medium text-slate-900">Rp {{ number_format($payment->price_amount, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Paid</dt>
                        <dd class="font-medium text-slate-900">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Outstanding</dt>
                        <dd class="font-medium {{ $payment->outstandingAmount() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">Rp {{ number_format($payment->outstandingAmount(), 0, ',', '.') }}</dd>
                    </div>
                    @if ($payment->notes)
                        <div>
                            <dt class="text-slate-500">Notes</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $payment->notes }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('payments.receipt', $payment) }}" class="inline-flex justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                        View Receipt
                    </a>
                    <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Delete this payment? The payment will be removed and linked attendances will become token debt.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-8 text-center text-slate-500 shadow-sm">No payments recorded.</div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-3xl bg-white shadow-sm md:block">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-6 py-3 font-medium">Student</th>
                    <th class="px-6 py-3 font-medium">Receipt</th>
                    <th class="px-6 py-3 font-medium">Package</th>
                    <th class="px-6 py-3 font-medium">Total</th>
                    <th class="px-6 py-3 font-medium">Remaining</th>
                    <th class="px-6 py-3 font-medium">Paid</th>
                    <th class="px-6 py-3 font-medium">Outstanding</th>
                    <th class="px-6 py-3 font-medium">Payment Date</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $payment->student->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $payment->displayReceiptNumber() }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $payment->displayLabel() }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $payment->total_sessions }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $payment->remaining_sessions }}</td>
                        <td class="px-6 py-4 text-slate-600">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 {{ $payment->outstandingAmount() > 0 ? 'font-medium text-amber-700' : 'text-emerald-700' }}">Rp {{ number_format($payment->outstandingAmount(), 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $payment->payment_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('payments.receipt', $payment) }}" class="text-sm font-medium text-slate-700">Receipt</a>
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Delete this payment? The payment will be removed and linked attendances will become token debt.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-slate-500">No payments recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payments->links() }}
    </div>
</x-app-layout>
