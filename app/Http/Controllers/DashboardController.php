<?php

namespace App\Http\Controllers;

use App\Models\Student;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $totalStudents = Student::count();
        $activeStudents = Student::active()->whereHas('payments', fn ($query) => $query->where('remaining_sessions', '>', 0))->count();
        $inactiveStudents = Student::active()->whereDoesntHave('payments', fn ($query) => $query->where('remaining_sessions', '>', 0))->count();
        $studentsNeedingPayment = Student::active()->with(['payments' => fn ($query) => $query->latest('payment_date')])
            ->get()
            ->filter(fn (Student $student) => $student->getRemainingSessions() === 0)
            ->take(5);

        return view('dashboard', compact(
            'totalStudents',
            'activeStudents',
            'inactiveStudents',
            'studentsNeedingPayment',
        ));
    }
}
