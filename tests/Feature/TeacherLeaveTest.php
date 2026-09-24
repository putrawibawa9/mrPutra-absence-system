<?php

namespace Tests\Feature;

use App\Models\TeacherAvailability;
use App\Models\TeacherLeave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherLeaveTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAY = '2026-09-28'; // hari Senin

    private function availability(User $teacher, string $day, string $start, string $end): void
    {
        TeacherAvailability::query()->create([
            'teacher_id' => $teacher->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'status' => TeacherAvailability::STATUS_AVAILABLE,
            'is_active' => true,
        ]);
    }

    public function test_teacher_can_submit_leave_request(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->post(route('my-leave.store'), [
            'date' => self::MONDAY,
            'reason' => 'Acara keluarga',
        ])->assertRedirect(route('my-leave.index'));

        $leave = TeacherLeave::query()->firstOrFail();
        $this->assertSame($teacher->id, $leave->teacher_id);
        $this->assertSame(TeacherLeave::STATUS_PENDING, $leave->status);
        $this->assertTrue($leave->isWholeDay());
    }

    public function test_leave_time_must_be_paired(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->post(route('my-leave.store'), [
            'date' => self::MONDAY,
            'start_time' => '09:00',
            // end_time hilang
        ])->assertSessionHasErrors('start_time');

        $this->assertSame(0, TeacherLeave::query()->count());
    }

    public function test_teacher_can_cancel_only_own_pending_leave(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $other = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $leave = TeacherLeave::query()->create(['teacher_id' => $teacher->id, 'date' => self::MONDAY, 'status' => TeacherLeave::STATUS_PENDING]);
        $othersLeave = TeacherLeave::query()->create(['teacher_id' => $other->id, 'date' => self::MONDAY, 'status' => TeacherLeave::STATUS_PENDING]);

        $this->actingAs($teacher)->delete(route('my-leave.destroy', $othersLeave))->assertForbidden();
        $this->actingAs($teacher)->delete(route('my-leave.destroy', $leave))->assertRedirect(route('my-leave.index'));
        $this->assertModelMissing($leave);
    }

    public function test_admin_index_recommends_available_substitutes_excluding_self_and_on_leave(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $onLeave = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Libur']);
        $free = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Pengganti']);
        $wrongDay = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Selasa']);
        $alsoOff = User::factory()->create(['role' => User::ROLE_TEACHER, 'name' => 'Guru Juga Libur']);

        // Ketersediaan mingguan.
        $this->availability($onLeave, 'monday', '09:00', '11:00');
        $this->availability($free, 'monday', '09:00', '11:00');
        $this->availability($wrongDay, 'tuesday', '09:00', '11:00');
        $this->availability($alsoOff, 'monday', '09:00', '11:00');

        // Guru utama ajukan libur Senin; guru lain juga libur di tanggal sama.
        $leave = TeacherLeave::query()->create(['teacher_id' => $onLeave->id, 'date' => self::MONDAY, 'status' => TeacherLeave::STATUS_PENDING]);
        TeacherLeave::query()->create(['teacher_id' => $alsoOff->id, 'date' => self::MONDAY, 'status' => TeacherLeave::STATUS_APPROVED]);

        $response = $this->actingAs($admin)->get(route('teacher-leaves.index'));
        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) use ($leave, $free, $onLeave, $wrongDay, $alsoOff) {
            $row = $rows->firstWhere('leave.id', $leave->id);
            $ids = collect($row->substitutes)->pluck('teacher_id')->all();

            return in_array($free->id, $ids, true)
                && ! in_array($onLeave->id, $ids, true)
                && ! in_array($wrongDay->id, $ids, true)
                && ! in_array($alsoOff->id, $ids, true);
        });
    }

    public function test_admin_can_approve_leave(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $leave = TeacherLeave::query()->create(['teacher_id' => $teacher->id, 'date' => self::MONDAY, 'status' => TeacherLeave::STATUS_PENDING]);

        $this->actingAs($admin)->post(route('teacher-leaves.approve', $leave))->assertRedirect();

        $leave->refresh();
        $this->assertSame(TeacherLeave::STATUS_APPROVED, $leave->status);
        $this->assertSame($admin->id, $leave->reviewed_by_user_id);
        $this->assertNotNull($leave->reviewed_at);
    }

    public function test_access_control(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Guru tidak boleh buka halaman admin.
        $this->actingAs($teacher)->get(route('teacher-leaves.index'))->assertForbidden();
        // Admin tidak masuk grup teacher-only.
        $this->actingAs($admin)->get(route('my-leave.index'))->assertForbidden();
    }
}
