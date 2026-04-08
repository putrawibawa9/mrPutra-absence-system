<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">{{ $student->name }}</h2>
                <p class="text-sm text-slate-500">Student profile, payment history, and attendances.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $student->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                    {{ $student->statusLabel() }}
                </span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $student->getRemainingSessions() > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                    {{ $student->getRemainingSessions() }} sessions left
                </span>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Student Details</h3>
            <dl class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Phone</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $student->phone }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Email</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $student->email ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Status</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $student->statusLabel() }}</dd>
                </div>
            </dl>
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('students.toggle-status', $student) }}" class="mt-6">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-xl px-4 py-2 text-sm font-medium {{ $student->is_active ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ $student->is_active ? 'Deactivate Student' : 'Activate Student' }}
                    </button>
                </form>
            @endif
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Latest Active Payment</h3>
            @if ($student->latestActivePayment)
                <div class="mt-4 space-y-2 text-sm text-slate-600">
                    <p><span class="font-medium text-slate-900">Source:</span> {{ $student->latestActivePayment->displayLabel() }}</p>
                    <p><span class="font-medium text-slate-900">Remaining:</span> {{ $student->latestActivePayment->remaining_sessions }}</p>
                    <p><span class="font-medium text-slate-900">Payment date:</span> {{ $student->latestActivePayment->payment_date->format('d M Y') }}</p>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">No active payment available.</p>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Payments</h3>
            </div>
            <div class="space-y-4 p-4 md:hidden">
                @forelse ($student->payments as $payment)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <h4 class="font-medium text-slate-900">{{ $payment->displayLabel() }}</h4>
                        <dl class="mt-3 grid gap-2 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">Total</dt>
                                <dd class="text-slate-900">{{ $payment->total_sessions }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">Remaining</dt>
                                <dd class="text-slate-900">{{ $payment->remaining_sessions }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">Date</dt>
                                <dd class="text-slate-900">{{ $payment->payment_date->format('d M Y') }}</dd>
                            </div>
                            @if ($payment->notes)
                                <div>
                                    <dt class="text-slate-500">Notes</dt>
                                    <dd class="text-slate-900">{{ $payment->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @empty
                    <p class="px-2 py-4 text-center text-sm text-slate-500">No payments recorded.</p>
                @endforelse
            </div>
            <table class="hidden min-w-full divide-y divide-slate-100 text-sm md:table">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Package</th>
                        <th class="px-6 py-3 font-medium">Total</th>
                        <th class="px-6 py-3 font-medium">Remaining</th>
                        <th class="px-6 py-3 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($student->payments as $payment)
                        <tr>
                            <td class="px-6 py-4 text-slate-900">{{ $payment->displayLabel() }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $payment->total_sessions }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $payment->remaining_sessions }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $payment->payment_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">No payments recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Attendances</h3>
            </div>
            <div class="space-y-4 p-4 md:hidden">
                @forelse ($student->attendances as $attendance)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-medium text-slate-900">{{ $attendance->teacher->name }}</p>
                            <p class="text-sm text-slate-500">{{ $attendance->date->format('d M Y') }}</p>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">{{ $attendance->notes ?: '-' }}</p>
                    </div>
                @empty
                    <p class="px-2 py-4 text-center text-sm text-slate-500">No attendances recorded.</p>
                @endforelse
            </div>
            <table class="hidden min-w-full divide-y divide-slate-100 text-sm md:table">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Date</th>
                        <th class="px-6 py-3 font-medium">Teacher</th>
                        <th class="px-6 py-3 font-medium">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($student->attendances as $attendance)
                        <tr>
                            <td class="px-6 py-4 text-slate-600">{{ $attendance->date->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-slate-900">{{ $attendance->teacher->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $attendance->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-500">No attendances recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
