<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherRequest;
use App\Models\User;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = User::query()
            ->where('role', User::ROLE_TEACHER)
            ->latest()
            ->paginate(10);

        return view('teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('teachers.create');
    }

    public function store(TeacherRequest $request)
    {
        User::create([
            'name' => $request->string('name')->toString(),
            'username' => Str::lower($request->string('username')->toString()),
            'email' => $request->string('email')->toString(),
            'password' => Str::random(32),
            'role' => User::ROLE_TEACHER,
        ]);

        return redirect()->route('teachers.index')->with('status', 'Teacher created successfully.');
    }

    public function edit(User $teacher)
    {
        abort_unless($teacher->isTeacher(), 404);

        return view('teachers.edit', compact('teacher'));
    }

    public function update(TeacherRequest $request, User $teacher)
    {
        abort_unless($teacher->isTeacher(), 404);

        $data = [
            'name' => $request->string('name')->toString(),
            'username' => Str::lower($request->string('username')->toString()),
            'email' => $request->string('email')->toString(),
        ];

        $teacher->update($data);

        return redirect()->route('teachers.index')->with('status', 'Teacher updated successfully.');
    }

    public function destroy(User $teacher)
    {
        abort_unless($teacher->isTeacher(), 404);

        $teacher->delete();

        return redirect()->route('teachers.index')->with('status', 'Teacher deleted successfully.');
    }
}
