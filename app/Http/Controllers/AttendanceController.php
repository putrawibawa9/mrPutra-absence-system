<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = Attendance::with(['batch', 'student', 'teacher', 'payment.package'])
            ->latest('date')
            ->paginate(10);

        return view('attendances.index', compact('attendances'));
    }

    public function create(Request $request)
    {
        $students = Student::active()
            ->with('latestActivePayment.package')
            ->orderBy('name')
            ->get();
        $selectedStudent = $request->integer('student_id');
        $activePayments = collect();
        $selectedPaymentId = null;

        if ($selectedStudent) {
            $activePayments = Payment::query()
                ->with('package')
                ->where('student_id', $selectedStudent)
                ->active()
                ->latest('payment_date')
                ->get();

            $selectedPaymentId = optional($activePayments->first())->id;
        }

        return view('attendances.create', compact('students', 'selectedStudent', 'activePayments', 'selectedPaymentId'));
    }

    public function store(AttendanceRequest $request)
    {
        if ($request->input('mode') === 'group') {
            return $this->storeGroupAttendance($request);
        }

        DB::transaction(function () use ($request): void {
            $payment = Payment::query()
                ->whereKey($request->integer('payment_id'))
                ->where('student_id', $request->integer('student_id'))
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->remaining_sessions <= 0) {
                throw ValidationException::withMessages([
                    'payment_id' => 'The selected payment has no remaining sessions.',
                ]);
            }

            Attendance::create([
                'student_id' => $request->integer('student_id'),
                'teacher_id' => $request->user()->id,
                'payment_id' => $payment->id,
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
            ]);

            $payment->decrement('remaining_sessions');
        });

        return redirect()->route('attendances.index')->with('status', 'Attendance saved and session deducted.');
    }

    protected function storeGroupAttendance(AttendanceRequest $request)
    {
        $studentIds = collect($request->input('student_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($request, $studentIds): void {
            $batch = AttendanceBatch::create([
                'title' => $request->string('group_title')->toString(),
                'teacher_id' => $request->user()->id,
                'date' => $request->date('date'),
                'notes' => $request->string('notes')->toString(),
            ]);

            foreach ($studentIds as $studentId) {
                $payment = Payment::query()
                    ->where('student_id', $studentId)
                    ->active()
                    ->latest('payment_date')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if (! $payment || $payment->remaining_sessions <= 0) {
                    throw ValidationException::withMessages([
                        'student_ids' => 'One or more selected students do not have an active payment with remaining sessions.',
                    ]);
                }

                Attendance::create([
                    'attendance_batch_id' => $batch->id,
                    'student_id' => $studentId,
                    'teacher_id' => $request->user()->id,
                    'payment_id' => $payment->id,
                    'date' => $request->date('date'),
                    'notes' => $request->string('notes')->toString(),
                ]);

                $payment->decrement('remaining_sessions');
            }
        });

        return redirect()->route('attendances.index')->with('status', 'Group attendance saved and sessions deducted for selected students.');
    }
}
