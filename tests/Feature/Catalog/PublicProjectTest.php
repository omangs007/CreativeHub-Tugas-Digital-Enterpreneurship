<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_open_projects_in_active_categories(): void
    {
        Project::factory()->create(['title' => 'Proyek Terbuka Satu']);
        Project::factory()->status(ProjectStatus::Closed)->create(['title' => 'Proyek Sudah Ditutup']);
        Project::factory()->status(ProjectStatus::InProgress)->create(['title' => 'Proyek Sedang Dikerjakan']);
        Project::factory()->for(Category::factory()->inactive())->create(['title' => 'Proyek Kategori Nonaktif']);

        $this->get('/projects')
            ->assertOk()
            ->assertSee('Proyek Terbuka Satu')
            ->assertDontSee('Proyek Sudah Ditutup')
            ->assertDontSee('Proyek Sedang Dikerjakan')
            ->assertDontSee('Proyek Kategori Nonaktif');
    }

    public function test_projects_can_be_searched_and_filtered(): void
    {
        $web = Category::factory()->create(['slug' => 'website-development']);
        Project::factory()->for($web)->create(['title' => 'Website Toko Bunga']);
        Project::factory()->create(['title' => 'Poster Lomba Kampus']);

        $this->get('/projects?kategori=website-development')->assertSee('Website Toko Bunga')->assertDontSee('Poster Lomba Kampus');
        $this->get('/projects?q=poster')->assertSee('Poster Lomba Kampus')->assertDontSee('Website Toko Bunga');
    }

    public function test_project_detail_is_public_only_while_open(): void
    {
        $open = Project::factory()->create();
        $closed = Project::factory()->status(ProjectStatus::Closed)->create();

        $this->get("/projects/{$open->id}")->assertOk()->assertSee($open->title)->assertDontSee($open->customer->email);
        $this->get("/projects/{$closed->id}")->assertNotFound();
        $this->get('/projects/999999')->assertNotFound();
    }

    public function test_empty_state_is_shown(): void
    {
        $this->get('/projects')->assertOk()->assertSee('Belum ada proyek terbuka');
    }

    public function test_database_rejects_inverted_budget(): void
    {
        $this->expectException(QueryException::class);

        Project::factory()->create(['budget_min' => 900_000, 'budget_max' => 100_000]);
    }
}
