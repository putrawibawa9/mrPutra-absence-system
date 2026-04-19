<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JokiProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_project_summary_and_latest_progress(): void
    {
        $project = Project::query()->create([
            'project_title' => 'Sistem Informasi Akademik',
            'client_name' => 'Andi',
            'total_cost' => 2500000,
        ]);

        $project->progresses()->create([
            'progress_note' => 'Bab 1 dan Bab 2 selesai.',
            'progress_percentage' => 45,
        ]);

        $response = $this->get(route('joki.dashboard'));

        $response->assertOk()
            ->assertSee('Total Projects')
            ->assertSee('Rp 2.500.000')
            ->assertSee('Sistem Informasi Akademik')
            ->assertSee('Bab 1 dan Bab 2 selesai.');
    }

    public function test_project_can_be_created_from_the_form(): void
    {
        $response = $this->post(route('projects.store'), [
            'project_title' => 'Aplikasi Penjualan',
            'client_name' => 'Budi',
            'total_cost' => 3000000,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'project_title' => 'Aplikasi Penjualan',
            'client_name' => 'Budi',
            'total_cost' => 3000000,
        ]);
    }

    public function test_progress_can_be_added_to_a_project(): void
    {
        $project = Project::query()->create([
            'project_title' => 'Sistem Pakar',
            'client_name' => 'Citra',
            'total_cost' => 1500000,
        ]);

        $response = $this->post(route('projects.progress.store', $project), [
            'progress_note' => 'Revisi metodologi sudah selesai.',
            'progress_percentage' => 70,
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('progresses', [
            'project_id' => $project->id,
            'progress_note' => 'Revisi metodologi sudah selesai.',
            'progress_percentage' => 70,
        ]);
    }
}
