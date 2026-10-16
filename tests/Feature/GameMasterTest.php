<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Game;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_game(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('games.store'), [
            'tema' => 'Animals Vocabulary',
            'link' => 'https://www.baamboozle.com/game/123',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('games.index'));
        $this->assertDatabaseHas('games', ['tema' => 'Animals Vocabulary', 'is_active' => true]);
    }

    public function test_game_link_must_be_valid_url(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('games.create'))
            ->post(route('games.store'), ['tema' => 'X', 'link' => 'bukan-url', 'is_active' => 1])
            ->assertSessionHasErrors('link');
    }

    public function test_game_in_use_is_deactivated_not_deleted(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = Student::query()->create(['name' => 'A', 'phone' => '0811', 'is_active' => true]);
        $game = Game::query()->create(['tema' => 'Used', 'link' => 'https://ex.com/g', 'is_active' => true]);

        Attendance::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'game_id' => $game->id,
            'date' => now()->toDateString(),
            'learning_journal' => 'x',
        ]);

        $this->actingAs($admin)->delete(route('games.destroy', $game))->assertRedirect(route('games.index'));
        $this->assertDatabaseHas('games', ['id' => $game->id, 'is_active' => false]);
    }

    public function test_keterangan_differentiates_same_theme_games(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $kids = Game::query()->create(['tema' => 'Animal', 'keterangan' => 'Kids', 'link' => 'https://ex.com/a', 'is_active' => true]);
        $adv = Game::query()->create(['tema' => 'Animal', 'keterangan' => 'Advanced', 'link' => 'https://ex.com/b', 'is_active' => true]);
        $plain = Game::query()->create(['tema' => 'Colors', 'link' => 'https://ex.com/c', 'is_active' => true]);

        $this->assertSame('Animal — Kids', $kids->label());
        $this->assertSame('Animal — Advanced', $adv->label());
        $this->assertSame('Colors', $plain->label());

        $this->actingAs($admin)->get(route('games.index'))
            ->assertOk()
            ->assertSee('Animal — Kids')
            ->assertSee('Animal — Advanced');
    }

    public function test_games_menu_is_admin_only(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher)->get(route('games.index'))->assertForbidden();
    }
}
