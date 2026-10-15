<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class PaymentInfoController extends Controller
{
    /**
     * Halaman publik "Cara Bayar" (diamankan signed URL) — menampilkan nominal
     * tagihan, rekening, dan QRIS. Linknya disisipkan di pesan tagihan WhatsApp.
     */
    public function show(Request $request, Student $student)
    {
        abort_unless($request->hasValidSignature(), 403);

        $student->load('latestSessionPayment');

        $sessions = null;
        $amount = null;
        if ($student->latestSessionPayment
            && $student->latestSessionPayment->total_sessions > 0
            && $student->latestSessionPayment->price_amount > 0) {
            $sessions = (int) $student->latestSessionPayment->total_sessions;
            $amount = (int) $student->latestSessionPayment->price_amount;
        }

        $qrisPath = public_path('images/qris.png');

        return view('payments.pay-info', [
            'student' => $student,
            'sessions' => $sessions,
            'amount' => $amount,
            'qrisUrl' => file_exists($qrisPath) ? asset('images/qris.png') : null,
        ]);
    }
}
