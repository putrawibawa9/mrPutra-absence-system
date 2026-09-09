<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class FeedbackController extends Controller
{
    /**
     * Form feedback publik (tanpa login). Tautan diamankan signed URL.
     */
    public function create(Student $student)
    {
        if ($student->feedbacks()->exists()) {
            return view('feedback.thanks', ['student' => $student, 'already' => true]);
        }

        $formAction = URL::temporarySignedRoute('feedback.store', now()->addDays(30), ['student' => $student->id]);

        return view('feedback.create', ['student' => $student, 'formAction' => $formAction]);
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'message' => ['required', 'string', 'max:2000'],
            'suggestion' => ['nullable', 'string', 'max:2000'],
            'allow_testimonial' => ['nullable', 'boolean'],
        ], [
            'rating.required' => 'Mohon beri rating 1-5.',
            'message.required' => 'Mohon isi pesan & kesan Anda.',
        ]);

        // Cegah pengisian ganda dari satu tautan.
        if (! $student->feedbacks()->exists()) {
            $student->feedbacks()->create([
                'rating' => $data['rating'],
                'message' => $data['message'],
                'suggestion' => $data['suggestion'] ?? null,
                'allow_testimonial' => $request->boolean('allow_testimonial'),
            ]);
        }

        return view('feedback.thanks', ['student' => $student, 'already' => false]);
    }
}
