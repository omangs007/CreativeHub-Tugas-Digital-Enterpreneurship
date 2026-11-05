<?php

namespace Tests\Feature\Customer;

use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Butuh map Roblox bertema sekolah',
            'category_id' => $this->category->id,
            'description' => 'Map untuk event sekolah, ada area lomba, panggung, dan spawn point untuk 30 pemain.',
            'budget_min' => '500.000',
            'budget_max' => '1500000',
            'deadline' => now()->addWeeks(3)->toDateString(),
            ...$overrides,
        ];
    }

    public function test_guest_is_redirected_and_admin_only_account_is_forbidden(): void
    {
        $this->get('/dashboard/projects')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->admin()->create())->get('/dashboard/projects')->assertForbidden();
    }

    public function test_customer_can_create_open_project(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->post('/dashboard/projects', $this->payload([
            'status' => 'completed',
            'customer_id' => $other->id,
        ]));

        $project = Project::sole();
        $response->assertSessionHasNoErrors()->assertRedirect("/dashboard/projects/{$project->id}");

        $this->assertSame($customer->id, $project->customer_id);
        $this->assertSame(ProjectStatus::Open, $project->status);
        $this->assertSame(500_000, $project->budget_min);
        $this->assertSame(1_500_000, $project->budget_max);

        $this->get("/projects/{$project->id}")->assertOk()->assertSee($project->title)->assertSee('Rp500.000 – Rp1.500.000');
    }

    public function test_project_input_is_validated(): void
    {
        $customer = User::factory()->customer()->create();
        $inactive = Category::factory()->inactive()->create();

        $cases = [
            'title' => ['title' => 'Pendek'],
            'category_id' => ['category_id' => $inactive->id],
            'description' => ['description' => 'Terlalu singkat'],
            'budget_max' => ['budget_min' => '1000000', 'budget_max' => '500000'],
            'budget_min' => ['budget_min' => '5000'],
            'deadline' => ['deadline' => now()->toDateString()],
        ];

        foreach ($cases as $field => $overrides) {
            $this->actingAs($customer)->post('/dashboard/projects', $this->payload($overrides))->assertSessionHasErrors($field);
        }

        $this->actingAs($customer)
            ->post('/dashboard/projects', $this->payload(['deadline' => now()->addYears(2)->toDateString()]))
            ->assertSessionHasErrors('deadline');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_budget_and_deadline_are_optional(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post('/dashboard/projects', $this->payload(['budget_min' => '', 'budget_max' => '', 'deadline' => '']))
            ->assertSessionHasNoErrors();

        $project = Project::sole();
        $this->assertNull($project->budget_min);
        $this->assertSame('Anggaran fleksibel', $project->budgetLabel());
    }

    public function test_customer_sees_only_own_projects(): void
    {
        $customer = User::factory()->customer()->create();
        Project::factory()->for($customer, 'customer')->create(['title' => 'Proyek Milik Saya Sendiri']);
        $foreign = Project::factory()->create(['title' => 'Proyek Milik Orang Lain']);

        $this->actingAs($customer)->get('/dashboard/projects')
            ->assertOk()
            ->assertSee('Proyek Milik Saya Sendiri')
            ->assertDontSee('Proyek Milik Orang Lain');

        $this->actingAs($customer)->get("/dashboard/projects/{$foreign->id}")->assertForbidden();
    }

    public function test_owner_can_update_open_project_only(): void
    {
        $customer = User::factory()->customer()->create();
        $project = Project::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->patch("/dashboard/projects/{$project->id}", $this->payload(['title' => 'Judul proyek yang diperbarui']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Judul proyek yang diperbarui', $project->fresh()->title);

        $project->close();

        $this->actingAs($customer)->get("/dashboard/projects/{$project->id}/edit")->assertForbidden();
        $this->actingAs($customer)->patch("/dashboard/projects/{$project->id}", $this->payload())->assertForbidden();
    }

    public function test_other_customer_cannot_edit_or_close_project(): void
    {
        $project = Project::factory()->create(['title' => 'Judul Asli Proyek']);
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->get("/dashboard/projects/{$project->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->patch("/dashboard/projects/{$project->id}", $this->payload())->assertForbidden();
        $this->actingAs($intruder)->post("/dashboard/projects/{$project->id}/close")->assertForbidden();

        $this->assertSame('Judul Asli Proyek', $project->fresh()->title);
        $this->assertSame(ProjectStatus::Open, $project->fresh()->status);
    }

    public function test_owner_can_close_open_project_once(): void
    {
        $customer = User::factory()->customer()->create();
        $project = Project::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)->post("/dashboard/projects/{$project->id}/close")->assertSessionHasNoErrors();

        $project->refresh();
        $this->assertSame(ProjectStatus::Closed, $project->status);
        $this->assertNotNull($project->closed_at);
        $this->get("/projects/{$project->id}")->assertNotFound();

        $this->actingAs($customer)->post("/dashboard/projects/{$project->id}/close")->assertSessionHasErrors('project');
    }
}
