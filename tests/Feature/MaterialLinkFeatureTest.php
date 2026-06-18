<?php

namespace Tests\Feature;

use App\Models\MaterialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_material_link(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('material-links.store'), [
            'title' => 'Speaking Chunk Bank',
            'url' => 'https://example.com/chunk-bank',
            'description' => 'Kumpulan frase siap pakai untuk speaking.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('material-links.index', absolute: false));

        $this->assertDatabaseHas('material_links', [
            'title' => 'Speaking Chunk Bank',
            'url' => 'https://example.com/chunk-bank',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_material_link_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        MaterialLink::create([
            'title' => 'Grammar Notes',
            'url' => 'https://example.com/grammar-notes',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('material-links.index'))
            ->assertOk()
            ->assertSee('Link Materi')
            ->assertSee('Grammar Notes')
            ->assertSee('https://example.com/grammar-notes');
    }

    public function test_admin_can_search_material_links(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        MaterialLink::create([
            'title' => 'Speaking Chunk Bank',
            'url' => 'https://example.com/chunk-bank',
            'description' => 'Frase siap pakai untuk speaking.',
            'is_active' => true,
        ]);
        MaterialLink::create([
            'title' => 'Grammar Notes',
            'url' => 'https://example.com/grammar-notes',
            'description' => 'Catatan grammar.',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('material-links.index', ['search' => 'chunk']))
            ->assertOk()
            ->assertSee('Speaking Chunk Bank')
            ->assertDontSee('Grammar Notes');
    }
}
