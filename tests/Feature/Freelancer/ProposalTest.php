<?php

namespace Tests\Feature\Freelancer;

use App\Enums\ProjectStatus;
use App\Enums\ProposalStatus;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'price' => '750.000',
            'delivery_days' => 10,
            'revisions' => 2,
            'message' => 'Saya berpengalaman membuat map Roblox untuk event sekolah dan dapat selesai tepat waktu.',
            ...$overrides,
        ];
    }

    public function test_freelancer_can_send_proposal_to_open_project(): void
    {
        $project = Project::factory()->create();
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)
            ->post("/dashboard/projects/{$project->id}/proposals", $this->payload(['status' => 'accepted', 'freelancer_id' => 999]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/proposals');

        $proposal = Proposal::sole();
        $this->assertSame($freelancer->id, $proposal->freelancer_id);
        $this->assertSame(ProposalStatus::Pending, $proposal->status);
        $this->assertSame(750_000, $proposal->price);

        $this->get("/projects/{$project->id}")->assertSee('1 penawaran');
    }

    public function test_freelancer_cannot_propose_on_own_project(): void
    {
        $user = User::factory()->freelancer()->create();
        $project = Project::factory()->for($user, 'customer')->create();

        $this->actingAs($user)->post("/dashboard/projects/{$project->id}/proposals", $this->payload())->assertForbidden();
        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_only_one_active_proposal_per_freelancer_per_project(): void
    {
        $project = Project::factory()->create();
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)->post("/dashboard/projects/{$project->id}/proposals", $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($freelancer)->post("/dashboard/projects/{$project->id}/proposals", $this->payload())->assertForbidden();

        $this->assertSame(1, Proposal::count());
    }

    public function test_database_blocks_duplicate_active_proposal(): void
    {
        $proposal = Proposal::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Proposal::factory()->create(['project_id' => $proposal->project_id, 'freelancer_id' => $proposal->freelancer_id]);
    }

    public function test_freelancer_can_propose_again_after_withdrawing(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $proposal = Proposal::factory()->for($freelancer, 'freelancer')->create();

        $this->actingAs($freelancer)->post("/dashboard/proposals/{$proposal->id}/withdraw")->assertSessionHasNoErrors();
        $this->assertSame(ProposalStatus::Withdrawn, $proposal->fresh()->status);

        $this->actingAs($freelancer)
            ->post("/dashboard/projects/{$proposal->project_id}/proposals", $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Proposal::count());
    }

    public function test_closed_or_in_progress_projects_do_not_accept_proposals(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        foreach ([ProjectStatus::Closed, ProjectStatus::InProgress, ProjectStatus::Completed] as $status) {
            $project = Project::factory()->status($status)->create();

            $this->actingAs($freelancer)->post("/dashboard/projects/{$project->id}/proposals", $this->payload())->assertForbidden();
        }

        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_customer_without_freelancer_role_cannot_propose(): void
    {
        $project = Project::factory()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post("/dashboard/projects/{$project->id}/proposals", $this->payload())->assertForbidden();
    }

    public function test_proposal_is_validated(): void
    {
        $project = Project::factory()->create();
        $freelancer = User::factory()->freelancer()->create();

        foreach ([
            'price' => ['price' => '0'],
            'delivery_days' => ['delivery_days' => 0],
            'revisions' => ['revisions' => 21],
            'message' => ['message' => 'Terlalu pendek'],
        ] as $field => $overrides) {
            $this->actingAs($freelancer)
                ->post("/dashboard/projects/{$project->id}/proposals", $this->payload($overrides))
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_freelancer_can_edit_own_pending_proposal_only(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $proposal = Proposal::factory()->for($freelancer, 'freelancer')->create();
        $other = Proposal::factory()->create();

        $this->actingAs($freelancer)->get("/dashboard/proposals/{$proposal->id}/edit")->assertOk();
        $this->actingAs($freelancer)
            ->patch("/dashboard/proposals/{$proposal->id}", $this->payload(['price' => '900000']))
            ->assertSessionHasNoErrors();
        $this->assertSame(900_000, $proposal->fresh()->price);

        $this->actingAs($freelancer)->patch("/dashboard/proposals/{$other->id}", $this->payload())->assertForbidden();
        $this->actingAs($freelancer)->post("/dashboard/proposals/{$other->id}/withdraw")->assertForbidden();

        $proposal->forceFill(['status' => ProposalStatus::Rejected])->save();
        $this->actingAs($freelancer)->patch("/dashboard/proposals/{$proposal->id}", $this->payload())->assertForbidden();
    }

    public function test_freelancer_proposal_list_shows_own_proposals(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        Proposal::factory()->for($freelancer, 'freelancer')->create();
        $foreign = Proposal::factory()->create();

        $this->actingAs($freelancer)->get('/dashboard/proposals')
            ->assertOk()
            ->assertDontSee($foreign->project->title);
    }
}
