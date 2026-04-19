<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'program_type' => ['nullable', 'string', 'in:coding,english'],
            'sort_tokens' => ['nullable', 'string', 'in:lowest,highest'],
        ]);

        $students = Student::with('latestActivePayment')
            ->withSum('payments', 'remaining_sessions')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('book_info', 'like', '%'.$search.'%')
                        ->orWhere('program_type', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['program_type'] ?? null, fn ($query, $programType) => $query->where('program_type', $programType))
            ->when(($filters['sort_tokens'] ?? null) === 'lowest', function ($query) {
                $query->orderByRaw('COALESCE(payments_sum_remaining_sessions, 0) asc')
                    ->orderBy('name');
            })
            ->when(($filters['sort_tokens'] ?? null) === 'highest', function ($query) {
                $query->orderByRaw('COALESCE(payments_sum_remaining_sessions, 0) desc')
                    ->orderBy('name');
            }, fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        return view('students.index', compact('students', 'filters'));
    }

    public function create()
    {
        return view('students.create');
    }

    public function store(StudentRequest $request)
    {
        $data = $request->validated();
        $data['deactivated_at'] = (bool) $data['is_active']
            ? null
            : Carbon::parse($data['registration_date'])->endOfDay();

        Student::create($data);

        return redirect()->route('students.index')->with('status', 'Student created successfully.');
    }

    public function show(Student $student)
    {
        $student->load([
            'payments.package',
            'attendances.teacher',
            'attendances.payment.package',
            'latestActivePayment',
        ]);

        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    public function update(StudentRequest $request, Student $student)
    {
        $data = $request->validated();

        if ((bool) $data['is_active']) {
            $data['deactivated_at'] = null;
        } elseif ($student->is_active) {
            $data['deactivated_at'] = now();
        } else {
            $data['deactivated_at'] = $student->deactivated_at ?? now();
        }

        $student->update($data);

        return redirect()->route('students.index')->with('status', 'Student updated successfully.');
    }

    public function toggleStatus(Student $student)
    {
        $student->update([
            'is_active' => ! $student->is_active,
            'deactivated_at' => $student->is_active ? now() : null,
        ]);

        return redirect()
            ->back()
            ->with('status', $student->is_active ? 'Student activated successfully.' : 'Student deactivated successfully.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')->with('status', 'Student deleted successfully.');
    }
}
