<?php

namespace Tests\Feature\Admin;

use App\Enums\ServiceStatus;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Resources\Services\Pages\ViewService;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_list_and_view_services(): void
    {
        $pending = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();
        $published = Service::factory()->published()->withPackages()->create();

        $this->actingAs($this->admin)->get('/admin/services')->assertOk();
        $this->actingAs($this->admin)->get("/admin/services/{$pending->id}")->assertOk()->assertSee($pending->title);

        Livewire::actingAs($this->admin)
            ->test(ListServices::class)
            ->assertCanSeeTableRecords([$pending, $published]);
    }

    public function test_non_admins_cannot_open_service_moderation(): void
    {
        $service = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();

        $this->actingAs(User::factory()->freelancer()->create())->get('/admin/services')->assertForbidden();
        $this->actingAs($service->freelancer)->get("/admin/services/{$service->id}")->assertForbidden();
    }

    public function test_admin_can_approve_pending_service(): void
    {
        $service = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();

        Livewire::actingAs($this->admin)
            ->test(ListServices::class)
            ->callTableAction('approve', $service)
            ->assertHasNoTableActionErrors();

        $service->refresh();
        $this->assertSame(ServiceStatus::Published, $service->status);
        $this->assertNotNull($service->published_at);
        $this->assertSame($this->admin->id, $service->moderated_by);
        $this->get("/services/{$service->slug}")->assertOk();

        $log = AuditLog::sole();
        $this->assertSame('service.approved', $log->action);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertTrue($log->auditable->is($service));
        $this->assertSame('pending_review', $log->old_values['status']);
        $this->assertSame('published', $log->new_values['status']);
    }

    public function test_admin_can_reject_with_reason_shown_to_freelancer(): void
    {
        $service = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();

        Livewire::actingAs($this->admin)
            ->test(ViewService::class, ['record' => $service->getRouteKey()])
            ->callAction('reject', data: ['reason' => 'Deskripsi paket belum jelas, lengkapi ruang lingkup.'])
            ->assertHasNoActionErrors();

        $service->refresh();
        $this->assertSame(ServiceStatus::Rejected, $service->status);
        $this->assertSame('Deskripsi paket belum jelas, lengkapi ruang lingkup.', $service->rejection_reason);
        $this->assertSame('service.rejected', AuditLog::sole()->action);

        $this->actingAs($service->freelancer)
            ->get('/dashboard/services')
            ->assertSee('Deskripsi paket belum jelas, lengkapi ruang lingkup.');
    }

    public function test_rejection_requires_reason(): void
    {
        $service = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();

        Livewire::actingAs($this->admin)
            ->test(ListServices::class)
            ->callTableAction('reject', $service, data: ['reason' => ''])
            ->assertHasTableActionErrors(['reason' => 'required']);

        $this->assertSame(ServiceStatus::PendingReview, $service->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_moderation_actions_are_hidden_for_non_pending_services(): void
    {
        foreach ([ServiceStatus::Draft, ServiceStatus::Published, ServiceStatus::Rejected] as $status) {
            $service = Service::factory()->status($status)->withPackages()->create();

            Livewire::actingAs($this->admin)
                ->test(ListServices::class)
                ->assertTableActionHidden('approve', $service)
                ->assertTableActionHidden('reject', $service);
        }
    }

    public function test_model_rejects_invalid_transitions(): void
    {
        $draft = Service::factory()->withPackages()->create();

        $this->expectException(ValidationException::class);

        $draft->approve($this->admin);
    }

    public function test_resubmission_clears_previous_rejection_reason(): void
    {
        $service = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();
        $service->reject($this->admin, 'Thumbnail kurang jelas.');

        $this->actingAs($service->freelancer)->post("/dashboard/services/{$service->id}/submit")->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame(ServiceStatus::PendingReview, $service->status);
        $this->assertNull($service->rejection_reason);
    }

    public function test_admin_dashboard_shows_pending_count(): void
    {
        Service::factory()->count(2)->status(ServiceStatus::PendingReview)->withPackages()->create();

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Layanan menunggu review');
    }
}
