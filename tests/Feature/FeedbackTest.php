<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function student(): Student
    {
        return Student::query()->create(['name' => 'Alumni A', 'phone' => '0811', 'is_active' => false, 'deactivated_at' => now()]);
    }

    public function test_student_can_submit_feedback_via_signed_link(): void
    {
        $student = $this->student();
        $url = URL::temporarySignedRoute('feedback.store', now()->addDay(), ['student' => $student->id]);

        $this->post($url, [
            'rating' => 5,
            'message' => 'Belajarnya menyenangkan.',
            'suggestion' => 'Tambah jam malam.',
            'allow_testimonial' => '1',
        ])->assertOk()->assertSee('Terima kasih');

        $this->assertDatabaseHas('feedbacks', [
            'student_id' => $student->id,
            'rating' => 5,
            'message' => 'Belajarnya menyenangkan.',
            'allow_testimonial' => true,
        ]);
    }

    public function test_feedback_link_requires_valid_signature(): void
    {
        $student = $this->student();

        $this->get(route('feedback.create', $student))->assertForbidden();
        $this->post(route('feedback.store', $student), [
            'rating' => 5,
            'message' => 'x',
        ])->assertForbidden();

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_feedback_validates_rating_and_message(): void
    {
        $student = $this->student();
        $url = URL::temporarySignedRoute('feedback.store', now()->addDay(), ['student' => $student->id]);

        $this->from(URL::temporarySignedRoute('feedback.create', now()->addDay(), ['student' => $student->id]))
            ->post($url, ['rating' => 9, 'message' => ''])
            ->assertSessionHasErrors(['rating', 'message']);

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_duplicate_feedback_is_ignored(): void
    {
        $student = $this->student();
        Feedback::query()->create(['student_id' => $student->id, 'rating' => 4, 'message' => 'Pertama']);

        $url = URL::temporarySignedRoute('feedback.store', now()->addDay(), ['student' => $student->id]);
        $this->post($url, ['rating' => 1, 'message' => 'Kedua'])->assertOk();

        $this->assertSame(1, Feedback::query()->where('student_id', $student->id)->count());
        $this->assertSame('Pertama', Feedback::query()->where('student_id', $student->id)->value('message'));
    }

    public function test_feedback_list_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = $this->student();
        Feedback::query()->create(['student_id' => $student->id, 'rating' => 5, 'message' => 'Mantap sekali']);

        $this->actingAs($admin)->get(route('follow-up.feedback'))
            ->assertOk()
            ->assertSee('Feedback Murid')
            ->assertSee('Mantap sekali')
            ->assertSee('Alumni A');

        $this->actingAs($teacher)->get(route('follow-up.feedback'))->assertForbidden();
    }
}
