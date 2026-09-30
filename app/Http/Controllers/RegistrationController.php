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
            'formatOptions' => Registration::formatOptions(),
            'goalOptions' => Registration::goalOptions(),
            'timeOptions' => Registration::timeOptionsWithRange(),
            'dayOptions' => WeeklyDay::options(),
        ]);
    }

    public function store(Request $request)
    {
        // Satu pendaftar bisa mendaftarkan beberapa murid sekaligus (mis. bareng
        // teman). Kontak & preferensi jadwal dipakai bersama; tiap murid jadi
        // satu baris pendaftaran agar bisa direview & diterima terpisah.
        $data = $request->validate([
            'students' => ['required', 'array', 'min:1', 'max:10'],
            'students.*.name' => ['required', 'string', 'max:255'],
            'students.*.age' => ['required', 'integer', 'min:1', 'max:120'],
            // Nomor WA per murid — langsung tersimpan ke masing-masing data.
            'students.*.phone' => ['required', 'string', 'max:40'],
            'format_preference' => ['required', Rule::in(array_keys(Registration::formatOptions()))],
            'goal' => ['required', Rule::in(array_keys(Registration::goalOptions()))],
            'available_days' => ['required', 'array', 'min:1'],
            'available_days.*' => [Rule::in(WeeklyDay::values())],
            'time_preferences' => ['required', 'array', 'min:1'],
            'time_preferences.*' => [Rule::in(array_keys(Registration::timeOptions()))],
        ], [
            'students.required' => 'Isi minimal satu murid.',
            'students.*.name.required' => 'Nama murid wajib diisi.',
            'students.*.age.required' => 'Umur murid wajib diisi.',
            'students.*.phone.required' => 'Nomor WhatsApp tiap murid wajib diisi.',
            'format_preference.required' => 'Pilih format les.',
            'goal.required' => 'Pilih tujuan les.',
            'available_days.required' => 'Pilih minimal satu hari yang bisa.',
            'time_preferences.required' => 'Pilih minimal satu preferensi waktu.',
        ]);

        $shared = [
            'format_preference' => $data['format_preference'],
            'goal' => $data['goal'],
            'available_days' => $data['available_days'],
            'time_preferences' => $data['time_preferences'],
            'program' => 'english', // Form ini khusus English course.
            'status' => Registration::STATUS_PENDING,
        ];

        $names = [];
        foreach ($data['students'] as $student) {
            Registration::create(array_merge($shared, [
                'student_name' => $student['name'],
                'age' => $student['age'],
                'phone' => $student['phone'],
            ]));
            $names[] = $student['name'];
        }

        return view('registrations.thanks', ['names' => $names]);
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
