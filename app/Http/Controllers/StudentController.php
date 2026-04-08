<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\Student;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('latestActivePayment')->latest()->paginate(10);

        return view('students.index', compact('students'));
    }

    public function create()
    {
        return view('students.create');
    }

    public function store(StudentRequest $request)
    {
        Student::create($request->validated());

        return redirect()->route('students.index')->with('status', 'Student created successfully.');
    }

    public function show(Student $student)
    {
        $student->load([
            'payments.package',
            'attendances.teacher',
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
        $student->update($request->validated());

        return redirect()->route('students.index')->with('status', 'Student updated successfully.');
    }

    public function toggleStatus(Student $student)
    {
        $student->update([
            'is_active' => ! $student->is_active,
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
