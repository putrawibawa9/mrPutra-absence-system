<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
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
        $signaturePath = $request->hasFile('signature')
            ? $request->file('signature')->store('signatures', 'public')
            : null;

        if ($request->string('source_type')->toString() === Payment::SOURCE_MANUAL) {
            $payment = Payment::create([
                'receipt_number' => $this->generateReceiptNumber(),
                'student_id' => $request->integer('student_id'),
                'package_id' => null,
                'source_type' => Payment::SOURCE_MANUAL,
                'total_sessions' => $request->integer('manual_total_sessions'),
                'remaining_sessions' => $request->integer('manual_remaining_sessions'),
                'payment_date' => $request->date('payment_date'),
                'notes' => $request->string('notes')->toString(),
                'signed_by_user_id' => $request->user()->id,
                'signature_path' => $signaturePath,
            ]);

            return redirect()->route('payments.receipt', $payment)->with('status', 'Manual token balance recorded successfully.');
        }

        $package = Package::query()->findOrFail($request->integer('package_id'));

        $payment = Payment::create([
            'receipt_number' => $this->generateReceiptNumber(),
            'student_id' => $request->integer('student_id'),
            'package_id' => $package->id,
            'source_type' => Payment::SOURCE_PACKAGE,
            'total_sessions' => $package->total_sessions,
            'remaining_sessions' => $package->total_sessions,
            'payment_date' => $request->date('payment_date'),
            'notes' => $request->string('notes')->toString(),
            'signed_by_user_id' => $request->user()->id,
            'signature_path' => $signaturePath,
        ]);

        return redirect()->route('payments.receipt', $payment)->with('status', 'Payment recorded successfully.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['student', 'package', 'signer']);

        return view('payments.receipt', compact('payment'));
    }

    protected function generateReceiptNumber(): string
    {
        return 'KWT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
