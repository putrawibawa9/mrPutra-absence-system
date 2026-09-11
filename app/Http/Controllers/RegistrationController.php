<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Student;
use App\Support\WeeklyDay;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    /**
     * Form pendaftaran publik (tanpa login).
     */
    public function create()
    {
        return view('registrations.create', [
            'programOptions' => Registration::programOptions(),
            'formatOptions' => Registration::formatOptions(),
            'timeOptions' => Registration::timeOptions(),
            'dayOptions' => WeeklyDay::options(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'program' => ['required', Rule::in(array_keys(Registration::programOptions()))],
            'format_preference' => ['nullable', Rule::in(array_keys(Registration::formatOptions()))],
            'goal' => ['nullable', 'string', 'max:2000'],
            'level_note' => ['nullable', 'string', 'max:255'],
            'available_days' => ['nullable', 'array'],
            'available_days.*' => [Rule::in(WeeklyDay::values())],
            'time_preferences' => ['nullable', 'array'],
            'time_preferences.*' => [Rule::in(array_keys(Registration::timeOptions()))],
            'referral_source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'student_name.required' => 'Nama murid wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'program.required' => 'Pilih program yang diminati.',
        ]);

        $data['status'] = Registration::STATUS_PENDING;
        Registration::create($data);

        return view('registrations.thanks', ['name' => $data['student_name']]);
    }

    /**
     * Daftar pendaftaran untuk admin (menunggu di atas).
     */
    public function index()
    {
        $registrations = Registration::query()
            ->with('student')
            ->orderByRaw("CASE WHEN status = '".Registration::STATUS_PENDING."' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        return view('registrations.index', [
            'registrations' => $registrations,
            'pendingCount' => $registrations->where('status', Registration::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Terima pendaftaran -> buat Murid aktif dari datanya.
     */
    public function accept(Request $request, Registration $registration)
    {
        if (! $registration->isPending()) {
            return redirect()->route('registrations.index')->with('status', 'Pendaftaran ini sudah diproses.');
        }

        $programType = $registration->program === 'coding'
            ? Student::PROGRAM_CODING
            : Student::PROGRAM_ENGLISH;

        $student = Student::create([
            'name' => $registration->student_name,
            'phone' => $registration->phone,
            'email' => $registration->email,
            'program_type' => $programType,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $registration->update([
            'status' => Registration::STATUS_ACCEPTED,
            'student_id' => $student->id,
            'processed_by_user_id' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return redirect()->route('students.show', $student)
            ->with('status', 'Pendaftaran diterima. Murid "'.$student->name.'" dibuat — silakan tentukan kelas & jadwalnya.');
    }

    public function reject(Request $request, Registration $registration)
    {
        if ($registration->isPending()) {
            $registration->update([
                'status' => Registration::STATUS_REJECTED,
                'processed_by_user_id' => $request->user()->id,
                'processed_at' => now(),
            ]);
        }

        return redirect()->route('registrations.index')->with('status', 'Pendaftaran ditolak.');
    }
}
