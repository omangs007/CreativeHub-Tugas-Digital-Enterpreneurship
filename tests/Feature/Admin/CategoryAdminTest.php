<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_category_and_it_is_audited(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Video Editing',
                'slug' => 'video-editing',
                'description' => 'Edit video YouTube dan TikTok.',
                'icon' => 'sparkles',
                'sort_order' => 4,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'video-editing')->sole();
        $this->assertTrue($category->is_active);

        $log = AuditLog::sole();
        $this->assertSame('category.created', $log->action);
        $this->assertSame($this->admin->id, $log->user_id);

        $this->get('/categories/video-editing')->assertOk();
    }

    public function test_category_slug_must_be_unique_and_valid(): void
    {
        Category::factory()->create(['slug' => 'sudah-ada']);

        Livewire::actingAs($this->admin)
            ->test(CreateCategory::class)
            ->fillForm(['name' => 'X', 'slug' => 'sudah-ada', 'icon' => 'code', 'sort_order' => 0])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);

        Livewire::actingAs($this->admin)
            ->test(CreateCategory::class)
            ->fillForm(['name' => 'X', 'slug' => 'ada spasi!', 'icon' => 'code', 'sort_order' => 0])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_deactivating_category_hides_it_and_is_audited(): void
    {
        $category = Category::factory()->create(['slug' => 'akan-nonaktif']);

        Livewire::actingAs($this->admin)
            ->test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($category->fresh()->is_active);
        $this->get('/categories/akan-nonaktif')->assertNotFound();

        $log = AuditLog::sole();
        $this->assertSame('category.updated', $log->action);
        $this->assertSame(['is_active' => true], $log->old_values);
        $this->assertSame(['is_active' => false], $log->new_values);
    }

    public function test_category_with_services_cannot_be_deleted(): void
    {
        $used = Category::factory()->create();
        Service::factory()->for($used)->create();
        $unused = Category::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ListCategories::class)
            ->assertTableActionHidden('delete', $used)
            ->assertTableActionVisible('delete', $unused)
            ->callTableAction('delete', $unused);

        $this->assertModelExists($used);
        $this->assertModelMissing($unused);
    }

    public function test_non_admin_cannot_manage_categories_or_read_audit_log(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)->get('/admin/categories')->assertForbidden();
        $this->actingAs($freelancer)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_admin_can_read_audit_log(): void
    {
        $category = Category::factory()->create();
        $this->actingAs($this->admin);
        $category->update(['name' => 'Nama Baru']);

        $this->get('/admin/audit-logs')->assertOk();

        Livewire::actingAs($this->admin)
            ->test(ListAuditLogs::class)
            ->assertCanSeeTableRecords(AuditLog::all())
            ->assertSee('category.updated');
    }

    public function test_seeder_changes_are_not_audited(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
