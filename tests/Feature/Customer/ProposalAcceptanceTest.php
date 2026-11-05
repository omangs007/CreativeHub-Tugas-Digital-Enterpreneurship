<?php

namespace Tests\Feature\Customer;

use App\Enums\OrderStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProposalStatus;
use App\Models\Order;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Services\OrderPlacement;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProposalAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        config(['creativehub.commission_rate_bps' => 1000]);
        $this->customer = User::factory()->customer()->create();
        $this->project = Project::factory()->for($this->customer, 'customer')->create([
            'title' => 'Map Roblox Event Sekolah',
            'description' => 'Map untuk lomba dan panggung, maksimal 30 pemain.',
        ]);
    }

    public function test_customer_sees_proposals_on_own_project(): void
    {
        $proposal = Proposal::factory()->for($this->project)->create(['message' => 'Penawaran dari freelancer berpengalaman membuat map.']);

        $this->actingAs($this->customer)
            ->get("/dashboard/projects/{$this->project->id}")
            ->assertOk()
            ->assertSee($proposal->freelancer->name)
            ->assertSee('Rp500.000')
            ->assertSee('Terima Penawaran');
    }

    public function test_accepting_proposal_creates_pending_order_with_snapshot(): void
    {
        $accepted = Proposal::factory()->for($this->project)->create([
            'price' => 1_234_567,
            'delivery_days' => 12,
            'revisions' => 3,
            'message' => 'Saya akan membuat map lengkap dengan area lomba.',
        ]);
        $other = Proposal::factory()->for($this->project)->create();

        $response = $this->actingAs($this->customer)->post("/dashboard/proposals/{$accepted->id}/accept");

        $order = Order::sole();
        $response->assertSessionHasNoErrors()->assertRedirect("/dashboard/orders/{$order->id}");

        $this->assertSame(ProposalStatus::Accepted, $accepted->fresh()->status);
        $this->assertSame(ProposalStatus::Rejected, $other->fresh()->status);
        $this->assertSame(ProjectStatus::InProgress, $this->project->fresh()->status);

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame($this->customer->id, $order->customer_id);
        $this->assertSame($accepted->freelancer_id, $order->freelancer_id);
        $this->assertSame($accepted->id, $order->proposal_id);
        $this->assertNull($order->service_package_id);
        $this->assertSame('Map Roblox Event Sekolah', $order->title);
        $this->assertStringContainsString('Saya akan membuat map lengkap', $order->scope_description);
        $this->assertSame(12, $order->delivery_days);
        $this->assertSame(3, $order->revisions_allowed);
        $this->assertSame(1_234_567, $order->total_amount);
        $this->assertSame(123_456, $order->commission_amount);
        $this->assertSame(1_111_111, $order->freelancer_amount);
        $this->assertSame($order->total_amount, $order->commission_amount + $order->freelancer_amount);
        $this->assertMatchesRegularExpression('/^ORD-\d{6}-[A-Z0-9]{6}$/', $order->order_number);

        $this->assertSame([null], $order->statusHistories->pluck('from_status')->all());
        $this->assertSame([OrderStatus::PendingPayment], $order->statusHistories->pluck('to_status')->all());

        // Snapshot tidak berubah walau proposal/proyek diedit setelahnya.
        DB::table('proposals')->where('id', $accepted->id)->update(['price' => 1, 'message' => 'diubah']);
        $this->assertSame(1_234_567, $order->fresh()->total_amount);
    }

    public function test_only_one_proposal_can_be_accepted(): void
    {
        [$first, $second] = Proposal::factory()->count(2)->for($this->project)->create();

        $this->actingAs($this->customer)->post("/dashboard/proposals/{$first->id}/accept")->assertSessionHasNoErrors();
        $this->actingAs($this->customer)->post("/dashboard/proposals/{$second->id}/accept")->assertForbidden();
        $this->actingAs($this->customer)->post("/dashboard/proposals/{$first->id}/accept")->assertForbidden();

        $this->assertSame(1, Order::count());
        $this->assertSame(1, Proposal::where('status', ProposalStatus::Accepted)->count());
    }

    public function test_service_rechecks_state_under_lock_for_double_submission(): void
    {
        [$first, $second] = Proposal::factory()->count(2)->for($this->project)->create();
        $placement = app(OrderPlacement::class);

        // Simulasi dua permintaan yang lolos pemeriksaan policy sebelum salah satunya selesai.
        $placement->acceptProposal($first, $this->customer);

        try {
            $placement->acceptProposal($second, $this->customer);
            $this->fail('Penerimaan kedua seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('proposal', $e->errors());
        }

        $this->assertSame(1, Order::count());
    }

    public function test_database_allows_only_one_accepted_proposal_per_project(): void
    {
        Proposal::factory()->for($this->project)->status(ProposalStatus::Accepted)->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Proposal::factory()->for($this->project)->status(ProposalStatus::Accepted)->create();
    }

    public function test_other_users_cannot_accept_or_reject(): void
    {
        $proposal = Proposal::factory()->for($this->project)->create();
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->post("/dashboard/proposals/{$proposal->id}/accept")->assertForbidden();
        $this->actingAs($intruder)->post("/dashboard/proposals/{$proposal->id}/reject")->assertForbidden();
        $this->actingAs($proposal->freelancer)->post("/dashboard/proposals/{$proposal->id}/accept")->assertForbidden();

        $this->assertSame(ProposalStatus::Pending, $proposal->fresh()->status);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_reject_single_proposal(): void
    {
        $proposal = Proposal::factory()->for($this->project)->create();

        $this->actingAs($this->customer)
            ->post("/dashboard/proposals/{$proposal->id}/reject")
            ->assertRedirect("/dashboard/projects/{$this->project->id}");

        $this->assertSame(ProposalStatus::Rejected, $proposal->fresh()->status);
        $this->assertSame(ProjectStatus::Open, $this->project->fresh()->status);
    }

    public function test_proposal_on_closed_project_cannot_be_accepted(): void
    {
        $proposal = Proposal::factory()->for($this->project)->create();
        $this->project->close();

        $this->actingAs($this->customer)->post("/dashboard/proposals/{$proposal->id}/accept")->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
    }
}
