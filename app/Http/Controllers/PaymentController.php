<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentInstallmentRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Attendance;
use App\Models\LearningModule;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Student;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(protected TokenService $tokenService)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'in:'.implode(',', [
                Payment::SOURCE_TOKEN,
                Payment::SOURCE_BOOK,
                Payment::SOURCE_PACKAGE,
                Payment::SOURCE_MANUAL,
            ])],
            'payment_status' => ['nullable', 'in:paid,partial,unpaid'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $payments = Payment::with(['student', 'learningModule', 'signer'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $search = trim((string) $search);

                $query->where(function ($query) use ($search) {
                    $query->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['source_type'] ?? null, fn ($query, $sourceType) => $query->where('source_type', $sourceType))
            ->when($filters['payment_status'] ?? null, function ($query, $status) {
                match ($status) {
                    'paid' => $query->whereColumn('amount_paid', '>=', 'price_amount'),
                    'partial' => $query->where('amount_paid', '>', 0)->whereColumn('amount_paid', '<', 'price_amount'),
                    'unpaid' => $query->where('amount_paid', '<=', 0)->where('price_amount', '>', 0),
                    default => null,
                };
            })
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '<=', $date))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', compact('payments', 'filters'));
    }

    public function create()
    {
        $students = Student::active()
            ->withSum('payments', 'remaining_sessions')
            ->withCount(['attendances as token_debt_count' => fn ($query) => $query->whereNull('payment_id')])
            ->orderBy('name')
            ->get();
        $learningModules = LearningModule::query()->active()->orderBy('name')->get();

        return view('payments.create', compact('students', 'learningModules'));
    }

    public function store(PaymentRequest $request)
    {
        if ($request->string('source_type')->toString() === Payment::SOURCE_BOOK) {
            $learningModule = $request->filled('learning_module_id')
                ? LearningModule::query()->findOrFail($request->integer('learning_module_id'))
                : null;
            $bookPrice = $learningModule?->price ?? $request->integer('book_price');
            $initialPaidAmount = $request->filled('initial_paid_amount')
                ? $request->integer('initial_paid_amount')
                : $bookPrice;

            $payment = DB::transaction(function () use ($request, $learningModule, $bookPrice, $initialPaidAmount): Payment {
                $payment = Payment::create([
                    'receipt_number' => $this->generateReceiptNumber(),
                    'student_id' => $request->integer('student_id'),
                    'learning_module_id' => $learningModule?->id,
                    'book_title' => $learningModule?->name ?? $request->string('book_title')->toString(),
                    'source_type' => Payment::SOURCE_BOOK,
                    'total_sessions' => 0,
                    'remaining_sessions' => 0,
                    'price_amount' => $bookPrice,
                    'amount_paid' => 0,
                    'payment_date' => $request->date('payment_date'),
                    'notes' => $request->string('notes')->toString(),
                    'signed_by_user_id' => $request->user()->id,
                ]);

                if ($initialPaidAmount > 0) {
                    $this->createInstallment(
                        payment: $payment,
                        amount: $initialPaidAmount,
                        paymentDate: $request->date('payment_date'),
                        notes: 'Initial payment',
                        receivedByUserId: $request->user()->id,
                    );
                }

                return $payment->refresh();
            });

            return redirect()->route('payments.receipt', $payment)->with('status', 'Book or module payment recorded successfully.');
        }

        $totalSessions = $request->integer('total_sessions');
        $price = $request->integer('price_amount');
        $initialPaidAmount = $request->filled('initial_paid_amount')
            ? $request->integer('initial_paid_amount')
            : $price;

        $division = $request->input('division');
        $format = $request->input('format');
        $learningMode = ($division && $format)
            ? \App\Models\Classroom::defaultLearningMode($division, $format)
            : null;

        $payment = DB::transaction(function () use ($request, $totalSessions, $price, $initialPaidAmount, $division, $format, $learningMode): Payment {
            $payment = Payment::create([
                'receipt_number' => $this->generateReceiptNumber(),
                'student_id' => $request->integer('student_id'),
                'source_type' => Payment::SOURCE_TOKEN,
                'division' => $division,
                'format' => $format,
                'learning_mode' => $learningMode,
                'total_sessions' => $totalSessions,
                'remaining_sessions' => $totalSessions,
                'price_amount' => $price,
                'amount_paid' => 0,
                'payment_date' => $request->date('payment_date'),
                'notes' => $request->string('notes')->toString(),
                'signed_by_user_id' => $request->user()->id,
            ]);

            if ($initialPaidAmount > 0) {
                $this->createInstallment(
                    payment: $payment,
                    amount: $initialPaidAmount,
                    paymentDate: $request->date('payment_date'),
                    notes: 'Initial payment',
                    receivedByUserId: $request->user()->id,
                );
            }

            $this->settleTokenDebt($payment);

            return $payment->refresh();
        });

        return redirect()->route('payments.receipt', $payment)->with('status', 'Pembayaran token berhasil disimpan.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['student', 'learningModule', 'signer', 'installments.receiver']);

        $publicReceiptUrl = URL::signedRoute('payments.public-receipt', ['payment' => $payment]);
        $whatsAppShareUrl = $this->buildWhatsAppShareUrl($payment, $publicReceiptUrl);

        return view('payments.receipt', [
            'payment' => $payment,
            'publicReceiptUrl' => $publicReceiptUrl,
            'whatsAppShareUrl' => $whatsAppShareUrl,
            'isPublicReceipt' => false,
        ]);
    }

    public function publicReceipt(Request $request, Payment $payment)
    {
        abort_unless($request->hasValidSignature(), 403);

        $payment->load(['student', 'learningModule', 'signer', 'installments.receiver']);

        $publicReceiptUrl = URL::signedRoute('payments.public-receipt', ['payment' => $payment]);

        return view('payments.receipt', [
            'payment' => $payment,
            'publicReceiptUrl' => $publicReceiptUrl,
            'whatsAppShareUrl' => null,
            'isPublicReceipt' => true,
        ]);
    }

    public function storeInstallment(PaymentInstallmentRequest $request, Payment $payment)
    {
        DB::transaction(function () use ($request, $payment): void {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->createInstallment(
                payment: $lockedPayment,
                amount: $request->integer('amount'),
                paymentDate: $request->date('payment_date'),
                notes: $request->string('notes')->toString(),
                receivedByUserId: $request->user()->id,
            );

            if ($lockedPayment->remaining_sessions > 0) {
                $this->settleTokenDebt($lockedPayment);
            }
        });

        return redirect()->route('payments.receipt', $payment)->with('status', 'Installment payment recorded successfully.');
    }

    public function reconcileDebt(Payment $payment)
    {
        $settledCount = DB::transaction(function () use ($payment): int {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            return $this->settleTokenDebt($lockedPayment);
        });

        $message = $settledCount > 0
            ? "Reconciled {$settledCount} debt attendance(s) successfully."
            : 'No debt attendance was available to reconcile for this payment.';

        return redirect()->route('payments.receipt', $payment)->with('status', $message);
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            Attendance::query()
                ->where('payment_id', $lockedPayment->id)
                ->update(['payment_id' => null]);

            $signaturePath = $lockedPayment->signature_path;

            $lockedPayment->delete();

            if ($signaturePath) {
                Storage::disk('public')->delete($signaturePath);
            }
        });

        return redirect()->route('payments.index')->with('status', 'Payment deleted successfully. Related attendances were converted to token debt.');
    }

    protected function generateReceiptNumber(): string
    {
        return 'KWT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    protected function settleTokenDebt(Payment $payment): int
    {
        if ($payment->remaining_sessions <= 0) {
            return 0;
        }

        $debtAttendances = Attendance::query()
            ->where('student_id', $payment->student_id)
            ->whereNull('payment_id')
            ->oldest('date')
            ->oldest('id')
            ->limit($payment->remaining_sessions)
            ->lockForUpdate()
            ->get();

        if ($debtAttendances->isEmpty()) {
            return 0;
        }

        // Attach each debt attendance to this payment and consume one token per
        // session so the ledger + remaining_sessions stay consistent.
        foreach ($debtAttendances as $attendance) {
            $attendance->update(['payment_id' => $payment->id]);
            $this->tokenService->consume($payment, $attendance, $attendance->date);
        }

        return $debtAttendances->count();
    }

    protected function createInstallment(Payment $payment, int $amount, $paymentDate, string $notes, int $receivedByUserId): PaymentInstallment
    {
        $installment = $payment->installments()->create([
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'notes' => $notes,
            'received_by_user_id' => $receivedByUserId,
        ]);

        $payment->increment('amount_paid', $amount);

        return $installment;
    }

    protected function buildWhatsAppShareUrl(Payment $payment, string $publicReceiptUrl): ?string
    {
        $whatsAppNumber = $payment->student->whatsappNumber();

        if (! $whatsAppNumber) {
            return null;
        }

        $message = implode("\n", [
            'Halo '.$payment->student->name.', terima kasih ya! 🙏',
            'Pembayaran Anda di Mr. Putra sudah kami terima.',
            '',
            '🧾 No. Kwitansi: '.$payment->displayReceiptNumber(),
            '📅 Tanggal: '.$payment->payment_date->format('d/m/Y'),
            '💰 Total: Rp '.number_format($payment->amount_paid, 0, ',', '.'),
            '',
            'E-kwitansi bisa dilihat & disimpan di sini:',
            $publicReceiptUrl,
            '',
            'Simpan pesan ini sebagai bukti pembayaran ya 😊',
        ]);

        return 'https://wa.me/'.$whatsAppNumber.'?text='.rawurlencode($message);
    }
}
