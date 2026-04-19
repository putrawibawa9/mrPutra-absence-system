<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentInstallmentRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Attendance;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['student', 'package', 'signer'])->latest('payment_date')->paginate(10);

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        $students = Student::active()->orderBy('name')->get();
        $packages = Package::orderBy('name')->get();

        return view('payments.create', compact('students', 'packages'));
    }

    public function store(PaymentRequest $request)
    {
        if ($request->string('source_type')->toString() === Payment::SOURCE_MANUAL) {
            $payment = DB::transaction(function () use ($request): Payment {
                $manualPrice = $request->filled('manual_price')
                    ? $request->integer('manual_price')
                    : 0;

                $payment = Payment::create([
                    'receipt_number' => $this->generateReceiptNumber(),
                    'student_id' => $request->integer('student_id'),
                    'package_id' => null,
                    'source_type' => Payment::SOURCE_MANUAL,
                    'total_sessions' => $request->integer('manual_total_sessions'),
                    'remaining_sessions' => $request->integer('manual_remaining_sessions'),
                    'price_amount' => $manualPrice,
                    'amount_paid' => $manualPrice,
                    'payment_date' => $request->date('payment_date'),
                    'notes' => $request->string('notes')->toString(),
                    'signed_by_user_id' => $request->user()->id,
                ]);

                $this->settleTokenDebt($payment);

                return $payment->refresh();
            });

            return redirect()->route('payments.receipt', $payment)->with('status', 'Manual token balance recorded successfully.');
        }

        if ($request->string('source_type')->toString() === Payment::SOURCE_BOOK) {
            $bookPrice = $request->integer('book_price');
            $initialPaidAmount = $request->filled('initial_paid_amount')
                ? $request->integer('initial_paid_amount')
                : $bookPrice;

            $payment = DB::transaction(function () use ($request, $bookPrice, $initialPaidAmount): Payment {
                $payment = Payment::create([
                    'receipt_number' => $this->generateReceiptNumber(),
                    'student_id' => $request->integer('student_id'),
                    'package_id' => null,
                    'book_title' => $request->string('book_title')->toString(),
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

        $package = Package::query()->findOrFail($request->integer('package_id'));
        $initialPaidAmount = $request->filled('initial_paid_amount')
            ? $request->integer('initial_paid_amount')
            : $package->price;

        $payment = DB::transaction(function () use ($request, $package, $initialPaidAmount): Payment {
            $payment = Payment::create([
                'receipt_number' => $this->generateReceiptNumber(),
                'student_id' => $request->integer('student_id'),
                'package_id' => $package->id,
                'source_type' => Payment::SOURCE_PACKAGE,
                'total_sessions' => $package->total_sessions,
                'remaining_sessions' => $package->total_sessions,
                'price_amount' => $package->price,
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

        return redirect()->route('payments.receipt', $payment)->with('status', 'Payment recorded successfully.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['student', 'package', 'signer', 'installments.receiver']);

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

        $payment->load(['student', 'package', 'signer', 'installments.receiver']);

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

        Attendance::query()
            ->whereKey($debtAttendances->modelKeys())
            ->update(['payment_id' => $payment->id]);

        $payment->decrement('remaining_sessions', $debtAttendances->count());

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
            'Halo '.$payment->student->name.',',
            'Berikut e-kwitansi pembayaran Anda di '.config('app.name').'.',
            'No. Kwitansi: '.$payment->displayReceiptNumber(),
            'Tanggal: '.$payment->payment_date->format('d/m/Y'),
            'Total: Rp '.number_format($payment->amount_paid, 0, ',', '.'),
            'Link receipt: '.$publicReceiptUrl,
        ]);

        return 'https://wa.me/'.$whatsAppNumber.'?text='.rawurlencode($message);
    }
}
