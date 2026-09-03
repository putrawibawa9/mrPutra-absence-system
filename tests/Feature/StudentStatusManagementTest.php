<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_optional_student_book_info(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'Book Student',
            'phone' => '0813333333',
            'email' => 'book@example.com',
            'book_info' => 'English File Elementary, Unit 4.',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response->assertRedirect(route('students.index', absolute: false));
        $this->assertDatabaseHas('students', [
            'name' => 'Book Student',
            'book_info' => 'English File Elementary, Unit 4.',
            'program_type' => Student::PROGRAM_ENGLISH,
        ]);

        $student = Student::query()->where('email', 'book@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('English File Elementary, Unit 4.');
    }

    public function test_student_index_can_search_by_name_phone_email_or_book_info(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Student::query()->create([
            'name' => 'Searchable Student',
            'phone' => '0813333333',
            'email' => 'searchable@example.com',
            'book_info' => 'Cambridge Primary 4',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        Student::query()->create([
            'name' => 'Hidden Student',
            'phone' => '0899999999',
            'email' => 'hidden@example.com',
            'book_info' => 'Different Book',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('students.index', [
            'search' => 'Cambridge',
        ]));

        $response->assertOk();
        $response->assertSee('Searchable Student');
        $response->assertDontSee('Hidden Student');
    }

    public function test_student_index_hides_inactive_students_by_default(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Student::query()->create([
            'name' => 'Active Listed Student',
            'phone' => '0813777001',
            'email' => 'active-listed@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Student::query()->create([
            'name' => 'Inactive Hidden Student',
            'phone' => '0813777002',
            'email' => 'inactive-hidden@example.com',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => now()->toDateString(),
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Active Listed Student')
            ->assertDontSee('Inactive Hidden Student');
    }

    public function test_student_pages_show_total_payment_debt_information(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create([
            'name' => 'Debt Info Student',
            'phone' => '0814444444',
            'email' => 'debtinfo@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'date' => now()->toDateString(),
            'learning_journal' => 'Debt lesson',
        ]);
        Payment::query()->create([
            'receipt_number' => 'KWT-DEBT-INFO-001',
            'student_id' => $student->id,
            'book_title' => 'Module TOEFL',
            'source_type' => Payment::SOURCE_BOOK,
            'total_sessions' => 0,
            'remaining_sessions' => 0,
            'price_amount' => 200000,
            'amount_paid' => 50000,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        // Sisa token 0 - utang 1 = saldo bersih -1 token.
        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Total Debt: Rp 150.000')
            ->assertSee('-1 token');

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Total Debt')
            ->assertSee('Rp 150.000')
            ->assertSee('Saldo Token')
            ->assertSee('-1 token');
    }

    public function test_student_show_page_lists_meeting_journals(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => 'Wulan',
        ]);
        $student = Student::query()->create([
            'name' => 'Journal Student',
            'phone' => '0814555000',
            'email' => 'journalstudent@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'date' => now()->toDateString(),
            'notes' => 'Focus on speaking confidence',
            'learning_journal' => 'Practiced speaking drills and page 12 reading.',
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Meeting Journals')
            ->assertSee('Wulan')
            ->assertSee('Focus on speaking confidence')
            ->assertSee('Practiced speaking drills and page 12 reading.');
    }

    public function test_student_show_page_lists_homework_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => 'Leo',
        ]);
        $student = Student::query()->create([
            'name' => 'Homework History Student',
            'phone' => '0814555009',
            'email' => 'homeworkhistory@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'payment_id' => null,
            'date' => now()->toDateString(),
            'notes' => 'Homework note',
            'learning_journal' => 'Speaking practice.',
            'homework_content' => "Memorize these chunks:\nHow was your day?\nIt was really exciting.",
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Homework History')
            ->assertSee('Leo')
            ->assertSee('How was your day?')
            ->assertSee('It was really exciting.');
    }

    public function test_admin_can_toggle_student_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Inactive Candidate',
            'phone' => '0811111111',
            'email' => 'candidate@example.com',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('students.toggle-status', $student));

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'is_active' => false,
        ]);
        $this->assertNotNull($student->fresh()->deactivated_at);
    }

    public function test_inactive_student_cannot_receive_new_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create([
            'name' => 'Inactive Student',
            'phone' => '0822222222',
            'email' => 'inactive@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => false,
        ]);
        $response = $this->from(route('payments.create'))
            ->actingAs($admin)
            ->post(route('payments.store'), [
                'student_id' => $student->id,
                'source_type' => Payment::SOURCE_TOKEN,
                'total_sessions' => 10,
                'price_amount' => 500000,
                'payment_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('payments.create', absolute: false));
        $response->assertSessionHasErrors('student_id');
        $this->assertDatabaseMissing('payments', [
            'student_id' => $student->id,
        ]);
    }

    public function test_student_pages_show_program_type_and_can_filter_by_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $codingStudent = Student::query()->create([
            'name' => 'Coding Student',
            'phone' => '0815000001',
            'email' => 'coding@example.com',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Student::query()->create([
            'name' => 'English Student',
            'phone' => '0815000002',
            'email' => 'english@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('students.index', ['program_type' => Student::PROGRAM_CODING]))
            ->assertOk()
            ->assertSee('Coding Student')
            ->assertSee('Coding')
            ->assertDontSee('English Student');

        $this->actingAs($admin)
            ->get(route('students.show', $codingStudent))
            ->assertOk()
            ->assertSee('Program')
            ->assertSee('Coding');
    }

    public function test_student_index_can_sort_by_remaining_tokens_and_show_low_token_warning(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $lowTokenStudent = Student::query()->create([
            'name' => 'Low Token Student',
            'phone' => '0815000010',
            'email' => 'low@example.com',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $highTokenStudent = Student::query()->create([
            'name' => 'High Token Student',
            'phone' => '0815000011',
            'email' => 'high@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Payment::query()->create([
            'receipt_number' => 'KWT-SORT-001',
            'student_id' => $lowTokenStudent->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 3,
            'remaining_sessions' => 2,
            'price_amount' => 0,
            'amount_paid' => 0,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        Payment::query()->create([
            'receipt_number' => 'KWT-SORT-002',
            'student_id' => $highTokenStudent->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 10,
            'remaining_sessions' => 8,
            'price_amount' => 0,
            'amount_paid' => 0,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('students.index', [
            'sort_tokens' => 'lowest',
        ]));

        $response->assertOk();
        $response->assertSee('Token terkecil ke terbesar');
        $response->assertSeeInOrder(['Low Token Student', 'High Token Student']);
        $response->assertSee('bg-amber-100', false);
    }

    public function test_admin_can_see_whatsapp_reminder_button_for_students_with_less_than_two_sessions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $lowSessionStudent = Student::query()->create([
            'name' => 'Reminder Student',
            'phone' => '0817000001',
            'email' => 'reminder@example.com',
            'program_type' => Student::PROGRAM_ENGLISH,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $enoughSessionStudent = Student::query()->create([
            'name' => 'Enough Session Student',
            'phone' => '0817000002',
            'email' => 'enough@example.com',
            'program_type' => Student::PROGRAM_CODING,
            'registration_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Payment::query()->create([
            'receipt_number' => 'KWT-REMINDER-001',
            'student_id' => $lowSessionStudent->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 4,
            'remaining_sessions' => 1,
            'price_amount' => 0,
            'amount_paid' => 0,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);
        Payment::query()->create([
            'receipt_number' => 'KWT-REMINDER-002',
            'student_id' => $enoughSessionStudent->id,
            'source_type' => Payment::SOURCE_MANUAL,
            'total_sessions' => 4,
            'remaining_sessions' => 3,
            'price_amount' => 0,
            'amount_paid' => 0,
            'payment_date' => now()->toDateString(),
            'signed_by_user_id' => $admin->id,
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('students.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Kirim Pengingat Sisa Pertemuan');
        $indexResponse->assertSee('wa.me/62817000001', false);
        $indexResponse->assertSee(rawurlencode("Halo Bapak/Ibu / Ananda, semoga sehat selalu.\n\nKami ingin menyampaikan bahwa sisa pertemuan untuk Ananda Reminder Student saat ini tinggal 1 kali pertemuan.\n\nApabila berkenan melanjutkan pertemuan berikutnya, kami siap membantu untuk penjadwalan dan informasi biaya les selanjutnya.\n\nTerima kasih banyak."), false);

        $showResponse = $this->actingAs($admin)->get(route('students.show', $lowSessionStudent));

        $showResponse->assertOk();
        $showResponse->assertSee('Kirim Pengingat Sisa Pertemuan');
        $showResponse->assertSee('wa.me/62817000001', false);

        $this->actingAs($admin)
            ->get(route('students.show', $enoughSessionStudent))
            ->assertOk()
            ->assertDontSee('Kirim Pengingat Sisa Pertemuan');
    }
}
