<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_book(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('books.store'), [
            'title' => 'English File Elementary',
            'notes' => 'edisi 4',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('books', ['title' => 'English File Elementary', 'is_active' => true]);
    }

    public function test_book_title_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Book::query()->create(['title' => 'Duplicate Book', 'is_active' => true]);

        $this->actingAs($admin)
            ->from(route('books.create'))
            ->post(route('books.store'), ['title' => 'Duplicate Book', 'is_active' => 1])
            ->assertSessionHasErrors('title');
    }

    public function test_book_in_use_is_deactivated_not_deleted(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $book = Book::query()->create(['title' => 'In Use Book', 'is_active' => true]);
        $classroom = Classroom::query()->create([
            'name' => 'Kelas Buku',
            'division' => Classroom::DIVISION_ENGLISH,
            'format' => Classroom::FORMAT_PRIVATE,
            'age_group' => Classroom::AGE_KIDS,
            'book_id' => $book->id,
            'is_active' => true,
        ]);
        $classroom->students()->attach($student);

        $this->actingAs($admin)->delete(route('books.destroy', $book))->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['id' => $book->id, 'is_active' => false]);
    }

    public function test_unused_book_can_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::query()->create(['title' => 'Unused Book', 'is_active' => true]);

        $this->actingAs($admin)->delete(route('books.destroy', $book))->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_books_menu_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('books.index'))->assertForbidden();
    }
}
