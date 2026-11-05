<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PackageTier;
use App\Enums\ProjectStatus;
use App\Filament\Pages\CommissionSettings;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\PortfolioItems\Pages\ListPortfolioItems;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderPlacement;
use App\Support\CommissionCalculator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_pages_are_reachable_and_forbidden_for_others(): void
    {
        $pages = ['/admin/users', '/admin/orders', '/admin/payments', '/admin/reviews', '/admin/projects', '/admin/portfolio-items', '/admin/commission-settings'];
        $freelancer = User::factory()->freelancer()->create();

        foreach ($pages as $page) {
            $this->actingAs($this->admin)->get($page)->assertOk();
            $this->actingAs($freelancer)->get($page)->assertForbidden();
        }
    }

    public function test_admin_can_deactivate_user_which_blocks_login_and_hides_listings(): void
    {
        $service = Service::factory()->published()->withPackages()->create();
        $freelancer = $service->freelancer;

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('toggleActive', $freelancer)
            ->assertHasNoTableActionErrors();

        $this->assertFalse($freelancer->fresh()->is_active);
        $this->assertSame('user.deactivated', AuditLog::sole()->action);

        // Layanan dan profil disembunyikan dari publik.
        $this->get("/services/{$service->slug}")->assertNotFound();
        $this->get("/freelancers/{$freelancer->id}")->assertNotFound();

        // Tidak bisa login.
        auth()->logout();
        $this->post('/login', ['email' => $freelancer->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Sesi yang masih berjalan dikeluarkan.
        $this->actingAs($freelancer->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('toggleActive', $this->admin);

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_inactive_admin_cannot_access_panel(): void
    {
        $this->admin->forceFill(['is_active' => false])->save();

        $this->assertFalse($this->admin->fresh()->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_orders_and_payments_are_listed_read_only(): void
    {
        $order = Order::factory()->create();

        Livewire::actingAs($this->admin)->test(ListOrders::class)
            ->assertCanSeeTableRecords([$order])
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');

        $this->actingAs($this->admin)->get("/admin/orders/{$order->id}")->assertOk()->assertSee($order->order_number);

        Livewire::actingAs($this->admin)->test(ListPayments::class)
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');

        $this->actingAs($this->admin)->get("/admin/orders/{$order->id}/edit")->assertNotFound();
    }

    public function test_admin_can_hide_review_with_audit(): void
    {
        $order = Order::factory()->status(OrderStatus::Completed)->create();
        $review = new Review;
        $review->order_id = $order->id;
        $review->customer_id = $order->customer_id;
        $review->freelancer_id = $order->freelancer_id;
        $review->rating = 2;
        $review->comment = 'Ulasan berisi kata tidak pantas';
        $review->save();

        Livewire::actingAs($this->admin)->test(ListReviews::class)->callTableAction('toggleVisibility', $review);

        $this->assertFalse($review->fresh()->is_visible);
        $this->assertSame('review.hidden', AuditLog::sole()->action);
    }

    public function test_admin_can_close_open_project_with_audit(): void
    {
        $project = Project::factory()->create();
        $closed = Project::factory()->status(ProjectStatus::Closed)->create();

        Livewire::actingAs($this->admin)->test(ListProjects::class)
            ->assertTableActionHidden('close', $closed)
            ->callTableAction('close', $project);

        $this->assertSame(ProjectStatus::Closed, $project->fresh()->status);
        $this->assertSame('project.closed_by_admin', AuditLog::sole()->action);
        $this->get("/projects/{$project->id}")->assertNotFound();
    }

    public function test_admin_can_hide_portfolio_item(): void
    {
        $item = PortfolioItem::factory()->create(['title' => 'Portofolio Bermasalah']);

        Livewire::actingAs($this->admin)->test(ListPortfolioItems::class)->callTableAction('toggleVisibility', $item);

        $this->assertFalse($item->fresh()->is_visible);
        $this->assertSame('portfolio.hidden', AuditLog::sole()->action);
        $this->get("/freelancers/{$item->user_id}")->assertDontSee('Portofolio Bermasalah');
    }

    public function test_commission_change_applies_to_new_orders_only_and_is_audited(): void
    {
        config(['creativehub.commission_rate_bps' => 1000]);
        $service = Service::factory()->published()->withPackages(100_000)->create();
        $package = $service->packages()->where('tier', PackageTier::Basic)->sole();
        $oldOrder = app(OrderPlacement::class)->orderPackage($package, User::factory()->customer()->create());

        Livewire::actingAs($this->admin)
            ->test(CommissionSettings::class)
            ->callAction('updateCommission', data: ['percent' => 12.5])
            ->assertHasNoActionErrors();

        $this->assertSame(1250, CommissionCalculator::currentRateBps());
        $log = AuditLog::sole();
        $this->assertSame('setting.commission_updated', $log->action);
        $this->assertSame(['commission_rate_bps' => 1000], $log->old_values);
        $this->assertSame(['commission_rate_bps' => 1250], $log->new_values);

        $newOrder = app(OrderPlacement::class)->orderPackage($package, User::factory()->customer()->create());

        $this->assertSame(10_000, $oldOrder->fresh()->commission_amount);
        $this->assertSame(12_500, $newOrder->commission_amount);
        $this->assertSame(87_500, $newOrder->freelancer_amount);
    }

    public function test_commission_is_validated(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CommissionSettings::class)
            ->callAction('updateCommission', data: ['percent' => 75])
            ->assertHasActionErrors(['percent']);

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
