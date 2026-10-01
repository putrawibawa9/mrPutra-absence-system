<?php

namespace Tests\Feature;

use App\Models\AttendanceBatch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherSalaryReportTest extends TestCase
{
    use RefreshDatabase;

    private function feeCategory(): ExpenseCategory
    {
        return ExpenseCategory::query()->create([
            'name' => ExpenseCategory::FEE_GURU_NAME,
            'is_active' => true,
            'is_system' => true,
        ]);
    }

    private function fee(ExpenseCategory $cat, User $teacher, int $amount, string $date, string $title = 'Fee guru'): void
    {
        // Tiap fee punya batch sendiri (unik per batch+guru di skema nyata).
        $batch = AttendanceBatch::query()->create(['title' => 'Kelas', 'teacher_id' => $teacher->id, 'date' => $date]);

        Expense::query()->create([
            'expense_category_id' => $cat->id,
            'teacher_user_id' => $teacher->id,
            'attendance_batch_id' => $batch->id,
            'title' => $title,
            'amount' => $amount,
            'expense_date' => $date,
        ]);
    }

    public function test_teacher_salary_sums_fee_per_teacher_within_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru A']);
        $b = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru B']);
        $cat = $this->feeCategory();

        $this->fee($cat, $a, 40000, '2026-10-05');
        $this->fee($cat, $a, 25000, '2026-10-06', 'Fee co-teacher - Guru A - Murid');
        $this->fee($cat, $b, 40000, '2026-10-07');
        // Di luar periode — tidak dihitung.
        $this->fee($cat, $a, 40000, '2026-09-20');

        $response = $this->actingAs($admin)->get(route('reports.teacher-salary', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31']));

        $response->assertOk();
        $response->assertViewHas('totalPayout', 105000);
        $response->assertViewHas('teacherCount', 2);
        $response->assertViewHas('sessionCount', 3);
        $response->assertViewHas('rows', function ($rows) use ($a) {
            $top = $rows->first(); // sorted by total desc → Guru A (65000)

            return $top->teacher->id === $a->id
                && (int) $top->total === 65000
                && (int) $top->session_count === 2
                && (int) $top->co_session_count === 1;
        });
    }

    public function test_teacher_salary_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('reports.teacher-salary'))->assertForbidden();
    }
}
