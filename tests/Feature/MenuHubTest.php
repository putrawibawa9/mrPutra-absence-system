<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_finance_hub_with_sub_menu_links(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('menu.show', 'finance'));

        $response->assertOk();
        $response->assertSee('Finance');
        $response->assertSee(route('reports.churn'));
        $response->assertSee(route('payments.index'));
    }

    public function test_single_item_section_redirects_to_its_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('menu.show', 'overview'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_unknown_section_returns_404(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('menu.show', 'nope'))->assertNotFound();
    }

    public function test_teacher_cannot_open_admin_only_section(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)->get(route('menu.show', 'finance'))->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('menu.show', 'finance'))->assertRedirect(route('login'));
    }
}
