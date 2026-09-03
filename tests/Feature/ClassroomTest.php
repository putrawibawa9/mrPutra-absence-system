<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBatch;
use App\Models\Classroom;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceTeacherFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    private function tokenPayment(Student $student, int $remaining = 5): Payment
    {
        return Payment::query()->create([
            'student_id' => $student->id,
            'source_type' => Payment::SOURCE_TOKEN,
            'total_sessions' => 10,
            'remaining_sessions' => $remaining,
            'price_amount' => 1000000,
            'amount_paid' => 1000000,
            'payment_date' => now()->toDateString(),
        ]);
    }

    private function makeClassroom(string $format, array $studentIds, string $division = Classroom::DIVISION_ENGLISH): Classroom
    {
        $classroom = Classroom::query()->create([
            'name' => Classroom::makeName($division, $format, Classroom::AGE_KIDS),
            'division' => $division,
            'format' => $format,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => true,
        ]);
        $classroom->students()->sync($studentIds);

        return $classroom;
    }

    public function test_admin_can_create_classroom_with_auto_generated_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('classrooms.store'), [
            'name' => '', // sengaja kosong -> harus auto-generate
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_SEMI,
            'age_group' => Classroom::AGE_TEENS_ADULT,
            'is_active' => 1,
            'student_ids' => [$a->id, $b->id],
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $classroom = Classroom::query()->firstOrFail();
        $this->assertSame('English · Semi · Teens/Adult', $classroom->name);
        $this->assertSame(Classroom::FORMAT_SEMI, $classroom->format);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $classroom->students->pluck('id')->all());
    }

    public function test_private_format_must_have_exactly_one_student(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);

        $response = $this->from(route('classrooms.create'))->actingAs($admin)->post(route('classrooms.store'), [
            'division' => Classroom::DIVISION_CODING,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => 1,
            'student_ids' => [$a->id, $b->id],
        ]);

        $response->assertRedirect(route('classrooms.create'));
        $response->assertSessionHasErrors('student_ids');
        $this->assertDatabaseCount('classrooms', 0);
    }

    public function test_semi_classroom_attendance_creates_batch_and_deducts_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);
        $payA = $this->tokenPayment($a);
        $payB = $this->tokenPayment($b);

        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id]);

        $response = $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id, $b->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Semi session.',
        ]);

        $response->assertRedirect(route('attendances.index'));
        $this->assertDatabaseCount('attendance_batches', 1);
        $batch = AttendanceBatch::query()->firstOrFail();
        $this->assertSame(2, Attendance::query()->where('attendance_batch_id', $batch->id)->count());
        $this->assertSame(4, $payA->fresh()->remaining_sessions);
        $this->assertSame(4, $payB->fresh()->remaining_sessions);

        $fees = Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->get();
        $this->assertCount(1, $fees);
        $this->assertSame($batch->id, (int) $fees->first()->attendance_batch_id);
        $this->assertSame(AttendanceTeacherFeeService::FEE_PER_MEETING, (int) $fees->first()->amount);
    }

    public function test_attendance_requires_learning_journal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $this->tokenPayment($a);
        $classroom = $this->makeClassroom(Classroom::FORMAT_PRIVATE, [$a->id]);

        $response = $this->from(route('classrooms.attendances.create', $classroom))
            ->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
                'date' => now()->toDateString(),
                'teacher_ids' => [$teacher->id],
                'present_student_ids' => [$a->id],
                'teaching_minutes' => 60,
                // learning_journal sengaja tidak dikirim
            ]);

        $response->assertRedirect(route('classrooms.attendances.create', $classroom));
        $response->assertSessionHasErrors('learning_journal');
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame(5, $a->payments()->first()->fresh()->remaining_sessions); // token tidak terpotong
    }

    public function test_private_classroom_attendance_creates_single_attendance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'Solo', 'phone' => '0811', 'is_active' => true]);
        $pay = $this->tokenPayment($a);

        $classroom = $this->makeClassroom(Classroom::FORMAT_PRIVATE, [$a->id]);

        $response = $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ]);

        $response->assertRedirect(route('attendances.index'));
        $this->assertDatabaseCount('attendance_batches', 0);
        $attendance = Attendance::query()->firstOrFail();
        $this->assertNull($attendance->attendance_batch_id);
        $this->assertSame(4, $pay->fresh()->remaining_sessions);

        $fee = Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->firstOrFail();
        $this->assertSame($attendance->id, (int) $fee->attendance_id);
    }

    public function test_private_no_show_records_nothing_and_keeps_token(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'Solo', 'phone' => '0811', 'is_active' => true]);
        $pay = $this->tokenPayment($a);

        $classroom = $this->makeClassroom(Classroom::FORMAT_PRIVATE, [$a->id]);

        // Kelas private (1-on-1): kalau muridnya tidak datang, sesi tidak dicatat
        // sama sekali. Tidak ada token yang hangus & tidak ada fee guru.
        $response = $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [], // murid tidak hadir
            'learning_journal' => 'Catatan sesi.',
        ]);

        $response->assertSessionHasErrors('present_student_ids');
        $this->assertSame(0, Attendance::query()->count());
        $this->assertDatabaseCount('attendance_batches', 0);
        $this->assertSame(5, $pay->fresh()->remaining_sessions); // token utuh, tidak hangus
    }

    public function test_self_paced_absent_student_forfeits_token(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);
        $payA = $this->tokenPayment($a);
        $payB = $this->tokenPayment($b);

        // Coding Semi is self-paced, tapi begitu sesi grup tercatat, murid yang
        // tidak hadir tetap kehilangan 1 token (sama seperti kelas synchronous).
        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id], Classroom::DIVISION_CODING);
        $this->assertTrue($classroom->isSelfPaced());

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id], // hanya A hadir, B tidak hadir
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        // Hanya A yang punya baris Attendance; B cukup di-forfeit di ledger.
        $this->assertSame(1, Attendance::query()->count());
        $this->assertSame(4, $payA->fresh()->remaining_sessions);
        $this->assertSame(4, $payB->fresh()->remaining_sessions); // B tidak hadir -> token hangus
    }

    public function test_synchronous_absent_student_forfeits_token_without_attendance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'Hadir', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'Bolos', 'phone' => '0812', 'is_active' => true]);
        $payA = $this->tokenPayment($a);
        $payB = $this->tokenPayment($b);

        // English Semi = synchronous (live cohort): bolos = token hangus (forfeited di ledger).
        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id], Classroom::DIVISION_ENGLISH);
        $this->assertTrue($classroom->isSynchronous());

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id], // hanya A hadir, B bolos
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        // Hanya yang hadir punya baris Attendance, tapi token KEDUANYA terpotong.
        $this->assertSame(1, Attendance::query()->count());
        $this->assertSame(0, Attendance::query()->where('student_id', $b->id)->count());
        $this->assertSame(4, $payA->fresh()->remaining_sessions);
        $this->assertSame(4, $payB->fresh()->remaining_sessions);

        // Tetap hanya 1 fee guru untuk 1 pertemuan.
        $fees = Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->count();
        $this->assertSame(1, $fees);
    }

    public function test_synchronous_forfeited_token_counts_in_cash_flow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'Hadir', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'Bolos', 'phone' => '0812', 'is_active' => true]);
        $this->tokenPayment($a); // 1.000.000 / 10 = 100.000 per sesi
        $this->tokenPayment($b);

        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id], Classroom::DIVISION_ENGLISH);
        $this->assertTrue($classroom->isSynchronous());

        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id], // B bolos -> token hangus
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        $response = $this->actingAs($admin)->get(route('cash-flow.index', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        // Hadir + bolos = 2 token x 100.000 = 200.000, dihitung sebagai 2 peserta dalam 1 sesi grup.
        $response->assertViewHas('incomeBySource', fn (array $source) => $source['student_payments'] === 200000);
        $response->assertViewHas('sessionTypeBreakdown', fn (array $breakdown) => $breakdown['group']['revenue'] === 200000
            && $breakdown['group']['participants'] === 2
            && $breakdown['group']['sessions'] === 1);
    }

    public function test_deleting_attendance_restores_token_and_removes_fee(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'Solo', 'phone' => '0811', 'is_active' => true]);
        $pay = $this->tokenPayment($a); // remaining 5

        $classroom = $this->makeClassroom(Classroom::FORMAT_PRIVATE, [$a->id]);
        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id],
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        $attendance = Attendance::query()->firstOrFail();
        $this->assertSame(4, $pay->fresh()->remaining_sessions);
        $this->assertSame(1, Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->count());

        $this->actingAs($admin)->delete(route('attendances.destroy', $attendance))->assertRedirect(route('attendances.index'));

        $this->assertSame(0, Attendance::query()->count());
        $this->assertSame(5, $pay->fresh()->remaining_sessions); // token dikembalikan
        $this->assertSame(0, Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->count()); // fee terhapus
    }

    public function test_deleting_batch_restores_consumed_and_forfeited_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $a = Student::query()->create(['name' => 'Hadir', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'Bolos', 'phone' => '0812', 'is_active' => true]);
        $payA = $this->tokenPayment($a);
        $payB = $this->tokenPayment($b);

        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id], Classroom::DIVISION_ENGLISH); // synchronous
        $this->actingAs($admin)->post(route('classrooms.attendances.store', $classroom), [
            'date' => now()->toDateString(),
            'teacher_ids' => [$teacher->id],
            'present_student_ids' => [$a->id], // B bolos -> forfeited
            'teaching_minutes' => 60,
            'learning_journal' => 'Catatan sesi.',
        ])->assertRedirect(route('attendances.index'));

        $batch = AttendanceBatch::query()->firstOrFail();
        $this->assertSame(4, $payA->fresh()->remaining_sessions);
        $this->assertSame(4, $payB->fresh()->remaining_sessions);

        $this->actingAs($admin)->delete(route('attendances.batches.destroy', $batch))->assertRedirect(route('attendances.index'));

        $this->assertSame(0, AttendanceBatch::query()->count());
        $this->assertSame(0, Attendance::query()->count());
        $this->assertSame(5, $payA->fresh()->remaining_sessions); // token hadir dikembalikan
        $this->assertSame(5, $payB->fresh()->remaining_sessions); // token bolos (forfeited) dikembalikan
        $this->assertSame(0, Expense::query()->whereHas('category', fn ($q) => $q->where('name', AttendanceTeacherFeeService::CATEGORY_NAME))->count());
    }

    public function test_student_already_in_active_class_cannot_join_another(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Student::query()->create(['name' => 'Sudah Kelas', 'phone' => '0811', 'is_active' => true]);

        $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id]); // kelas aktif berisi A

        $response = $this->from(route('classrooms.create'))->actingAs($admin)->post(route('classrooms.store'), [
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => 1,
            'student_ids' => [$a->id],
        ]);

        $response->assertRedirect(route('classrooms.create'));
        $response->assertSessionHasErrors('student_ids');
        $this->assertSame(1, Classroom::query()->count()); // tidak ada kelas baru
    }

    public function test_create_form_locks_only_students_actually_in_a_class(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $inClass = Student::query()->create(['name' => 'Sudah Kelas', 'phone' => '0811', 'is_active' => true]);
        $free = Student::query()->create(['name' => 'Belum Kelas', 'phone' => '0812', 'is_active' => true]);

        $this->makeClassroom(Classroom::FORMAT_SEMI, [$inClass->id]);

        $response = $this->actingAs($admin)->get(route('classrooms.create'));

        $response->assertOk();
        // Map harus ber-key student_id sungguhan (bukan posisi): yang di kelas terkunci,
        // yang belum tidak.
        $response->assertViewHas('takenStudents', fn (array $map) => array_key_exists($inClass->id, $map)
            && ! array_key_exists($free->id, $map));
    }

    public function test_editing_class_keeps_its_own_students_without_conflict(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $b = Student::query()->create(['name' => 'B', 'phone' => '0812', 'is_active' => true]);

        $classroom = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id, $b->id]);

        $response = $this->actingAs($admin)->put(route('classrooms.update', $classroom), [
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_SEMI,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => 1,
            'student_ids' => [$a->id, $b->id], // anggota sama, tidak boleh dianggap konflik
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $response->assertSessionHasNoErrors();
    }

    public function test_student_in_inactive_class_can_join_a_new_class(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);

        $old = $this->makeClassroom(Classroom::FORMAT_SEMI, [$a->id]);
        $old->update(['is_active' => false]); // kelas lama diarsipkan

        $response = $this->actingAs($admin)->post(route('classrooms.store'), [
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
            'is_active' => 1,
            'student_ids' => [$a->id],
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $this->assertSame(2, Classroom::query()->count());
    }
}
