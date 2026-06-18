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

    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('payments.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <x-input-label for="search" value="Search Student / Receipt" />
                <x-text-input
                    id="search"
                    name="search"
                    type="text"
                    class="mt-1 block w-full rounded-xl border-slate-300"
                    :value="$filters['search'] ?? null"
                    placeholder="Search by student name, phone, email, or receipt number"
                />
            </div>

            <div>
                <x-input-label for="source_type" value="Jenis Pembayaran" />
                <select id="source_type" name="source_type" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">Semua jenis</option>
                    <option value="token" @selected(($filters['source_type'] ?? null) === 'token')>Token Kelas</option>
                    <option value="book" @selected(($filters['source_type'] ?? null) === 'book')>Buku / Modul</option>
                </select>
            </div>

            <div>
                <x-input-label for="payment_status" value="Payment Status" />
                <select id="payment_status" name="payment_status" class="mt-1 block w-full rounded-xl border-slate-300">
                    <option value="">All statuses</option>
                    <option value="paid" @selected(($filters['payment_status'] ?? null) === 'paid')>Paid</option>
                    <option value="partial" @selected(($filters['payment_status'] ?? null) === 'partial')>Partial</option>
                    <option value="unpaid" @selected(($filters['payment_status'] ?? null) === 'unpaid')>Unpaid</option>
                </select>
            </div>

            <div>
                <x-input-label for="date_from" value="Date From" />
                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_from'] ?? null" />
            </div>

            <div>
                <x-input-label for="date_to" value="Date To" />
                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full rounded-xl border-slate-300" :value="$filters['date_to'] ?? null" />
            </div>

            <div class="flex flex-col gap-3 md:col-span-2 md:flex-row md:items-end xl:col-span-2">
                <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white md:w-auto">
                    Filter
                </button>
                <a href="{{ route('payments.index') }}" class="inline-flex w-full justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 md:w-auto">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="space-y-4 md:hidden">
        @forelse ($payments as $payment)
            <div class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $payment->student->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $payment->student->phone }}</p>
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
                    <th class="px-6 py-3 font-medium">Keterangan</th>
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
                        <td class="px-6 py-4 font-medium text-slate-900">
                            <p>{{ $payment->student->name }}</p>
                            <p class="mt-1 text-xs font-normal text-slate-500">{{ $payment->student->phone }}</p>
                        </td>
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
