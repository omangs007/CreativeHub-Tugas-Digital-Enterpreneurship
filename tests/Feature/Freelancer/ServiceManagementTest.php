<?php

namespace Tests\Feature\Freelancer;

use App\Enums\PackageTier;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->category = Category::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'title' => 'Desain Map Obby Roblox Custom',
            'category_id' => $this->category->id,
            'description' => 'Map obby dengan tema pilihan, checkpoint, dan uji main sebelum diserahkan.',
            'packages' => [
                'basic' => ['enabled' => '1', 'name' => 'Basic', 'description' => '10 stage obby.', 'price' => '150000', 'delivery_days' => '7', 'revisions' => '1'],
                'standard' => ['enabled' => '1', 'name' => 'Standard', 'description' => '25 stage obby.', 'price' => '350000', 'delivery_days' => '10', 'revisions' => '2'],
                'premium' => ['enabled' => '0', 'name' => '', 'description' => '', 'price' => '', 'delivery_days' => '', 'revisions' => ''],
            ],
        ], $overrides);
    }

    public function test_guest_and_customer_cannot_manage_services(): void
    {
        $this->get('/dashboard/services')->assertRedirect(route('login'));

        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->get('/dashboard/services')->assertForbidden();
        $this->actingAs($customer)->post('/dashboard/services', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('services', 0);
    }

    public function test_freelancer_sees_only_own_services(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        Service::factory()->withPackages()->for($freelancer, 'freelancer')->create(['title' => 'Layanan Saya Sendiri']);
        Service::factory()->withPackages()->create(['title' => 'Layanan Orang Lain']);

        $this->actingAs($freelancer)
            ->get('/dashboard/services')
            ->assertOk()
            ->assertSee('Layanan Saya Sendiri')
            ->assertDontSee('Layanan Orang Lain');
    }

    public function test_create_form_is_displayed(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)
            ->get('/dashboard/services/create')
            ->assertOk()
            ->assertSee($this->category->name)
            ->assertSeeInOrder(['Basic', 'Standard', 'Premium']);
    }

    public function test_freelancer_can_create_draft_service_with_packages(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $other = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)
            ->post('/dashboard/services', $this->payload([
                'status' => 'published',
                'user_id' => $other->id,
                'slug' => 'slug-palsu',
                'thumbnail' => UploadedFile::fake()->image('sampul.png', 1200, 750),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/services');

        $service = Service::sole();

        $this->assertSame($freelancer->id, $service->user_id);
        $this->assertSame(ServiceStatus::Draft, $service->status);
        $this->assertNull($service->published_at);
        $this->assertSame('desain-map-obby-roblox-custom', $service->slug);
        $this->assertStringStartsWith("services/{$freelancer->id}/", $service->thumbnail_path);
        Storage::disk('public')->assertExists($service->thumbnail_path);

        $packages = $service->packages()->get()->keyBy(fn ($p) => $p->tier->value);
        $this->assertCount(2, $packages);
        $this->assertSame(150_000, $packages['basic']->price);
        $this->assertSame(350_000, $packages['standard']->price);
        $this->assertTrue($packages['standard']->is_active);

        $this->get("/services/{$service->slug}")->assertNotFound();
    }

    public function test_slug_is_made_unique_by_server(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        Service::factory()->create(['slug' => 'desain-map-obby-roblox-custom']);

        $this->actingAs($freelancer)->post('/dashboard/services', $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($freelancer)->post('/dashboard/services', $this->payload())->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            ['desain-map-obby-roblox-custom', 'desain-map-obby-roblox-custom-2', 'desain-map-obby-roblox-custom-3'],
            Service::pluck('slug')->all(),
        );
    }

    public function test_price_with_thousand_separator_is_accepted(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)
            ->post('/dashboard/services', $this->payload(['packages' => ['basic' => ['price' => '1.250.000']]]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1_250_000, ServicePackage::where('tier', 'basic')->value('price'));
    }

    public function test_service_input_is_validated(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $inactive = Category::factory()->inactive()->create();

        $invalid = [
            'title' => [['title' => 'Pendek']],
            'category_id' => [['category_id' => $inactive->id]],
            'thumbnail' => [['thumbnail' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')]],
            'packages.basic.price' => [
                ['packages' => ['basic' => ['price' => '0']]],
                ['packages' => ['basic' => ['price' => '-5000']]],
                ['packages' => ['basic' => ['price' => '150000.50']]],
                ['packages' => ['basic' => ['price' => '999999999999']]],
            ],
            'packages.basic.delivery_days' => [['packages' => ['basic' => ['delivery_days' => '0']]]],
            'packages' => [
                ['packages' => ['basic' => ['enabled' => '0'], 'standard' => ['enabled' => '0']]],
                ['packages' => ['ultimate' => ['enabled' => '1', 'name' => 'X', 'description' => 'X', 'price' => '1000', 'delivery_days' => '1', 'revisions' => '0']]],
            ],
        ];

        foreach ($invalid as $field => $cases) {
            foreach ($cases as $overrides) {
                $this->actingAs($freelancer)
                    ->post('/dashboard/services', $this->payload($overrides))
                    ->assertSessionHasErrors($field);
            }
        }

        $this->assertDatabaseCount('services', 0);
    }

    public function test_owner_can_update_service_and_packages(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $service = Service::factory()->withPackages()->for($freelancer, 'freelancer')->create(['slug' => 'slug-tetap']);

        $this->actingAs($freelancer)->get("/dashboard/services/{$service->id}/edit")->assertOk();

        $this->actingAs($freelancer)
            ->patch("/dashboard/services/{$service->id}", $this->payload(['title' => 'Judul Layanan yang Diperbarui']))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/services');

        $service->refresh();
        $this->assertSame('Judul Layanan yang Diperbarui', $service->title);
        $this->assertSame('slug-tetap', $service->slug);

        $packages = $service->packages()->get()->keyBy(fn ($p) => $p->tier->value);
        $this->assertSame(150_000, $packages['basic']->price);
        // Paket yang dinonaktifkan disimpan sebagai nonaktif, tidak dihapus.
        $this->assertFalse($packages['premium']->is_active);
    }

    public function test_editing_published_service_sends_it_back_to_review(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $service = Service::factory()->published()->withPackages()->for($freelancer, 'freelancer')->create();

        $this->actingAs($freelancer)
            ->patch("/dashboard/services/{$service->id}", $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(ServiceStatus::PendingReview, $service->fresh()->status);
        $this->get("/services/{$service->slug}")->assertNotFound();
    }

    public function test_replacing_thumbnail_deletes_old_file(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        Storage::disk('public')->put('services/lama.png', 'x');
        $service = Service::factory()->withPackages()->for($freelancer, 'freelancer')->create(['thumbnail_path' => 'services/lama.png']);

        $this->actingAs($freelancer)
            ->patch("/dashboard/services/{$service->id}", $this->payload(['thumbnail' => UploadedFile::fake()->image('baru.jpg')]))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('services/lama.png');
        Storage::disk('public')->assertExists($service->fresh()->thumbnail_path);
    }

    public function test_other_freelancer_cannot_touch_service(): void
    {
        $owner = User::factory()->freelancer()->create();
        $intruder = User::factory()->freelancer()->create();
        $service = Service::factory()->withPackages()->for($owner, 'freelancer')->create(['title' => 'Milik Owner Asli']);

        $this->actingAs($intruder)->get("/dashboard/services/{$service->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->patch("/dashboard/services/{$service->id}", $this->payload())->assertForbidden();
        $this->actingAs($intruder)->post("/dashboard/services/{$service->id}/submit")->assertForbidden();
        $this->actingAs($intruder)->post("/dashboard/services/{$service->id}/withdraw")->assertForbidden();
        $this->actingAs($intruder)->delete("/dashboard/services/{$service->id}")->assertForbidden();

        $this->assertSame('Milik Owner Asli', $service->fresh()->title);
    }

    public function test_draft_can_be_submitted_for_review(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $service = Service::factory()->withPackages()->for($freelancer, 'freelancer')->create();

        $this->actingAs($freelancer)
            ->post("/dashboard/services/{$service->id}/submit")
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/services');

        $this->assertSame(ServiceStatus::PendingReview, $service->fresh()->status);
    }

    public function test_rejected_service_can_be_resubmitted(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $service = Service::factory()->status(ServiceStatus::Rejected)->withPackages()->for($freelancer, 'freelancer')->create();

        $this->actingAs($freelancer)->post("/dashboard/services/{$service->id}/submit")->assertSessionHasNoErrors();

        $this->assertSame(ServiceStatus::PendingReview, $service->fresh()->status);
    }

    public function test_service_without_active_package_cannot_be_submitted(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $service = Service::factory()->for($freelancer, 'freelancer')->create();
        ServicePackage::factory()->for($service)->inactive()->create(['tier' => PackageTier::Basic]);

        $this->actingAs($freelancer)
            ->post("/dashboard/services/{$service->id}/submit")
            ->assertSessionHasErrors('service');

        $this->assertSame(ServiceStatus::Draft, $service->fresh()->status);
    }

    public function test_published_or_pending_service_cannot_be_submitted_again(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        foreach ([ServiceStatus::Published, ServiceStatus::PendingReview] as $status) {
            $service = Service::factory()->status($status)->withPackages()->for($freelancer, 'freelancer')->create();

            $this->actingAs($freelancer)
                ->post("/dashboard/services/{$service->id}/submit")
                ->assertSessionHasErrors('service');

            $this->assertSame($status, $service->fresh()->status);
        }
    }

    public function test_published_service_can_be_withdrawn_to_draft(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $service = Service::factory()->published()->withPackages()->for($freelancer, 'freelancer')->create();

        $this->actingAs($freelancer)
            ->post("/dashboard/services/{$service->id}/withdraw")
            ->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame(ServiceStatus::Draft, $service->status);
        $this->assertNull($service->published_at);
        $this->get("/services/{$service->slug}")->assertNotFound();
    }

    public function test_draft_can_be_deleted_with_packages_and_thumbnail(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        Storage::disk('public')->put('services/hapus.png', 'x');
        $service = Service::factory()->withPackages()->for($freelancer, 'freelancer')->create(['thumbnail_path' => 'services/hapus.png']);

        $this->actingAs($freelancer)
            ->delete("/dashboard/services/{$service->id}")
            ->assertRedirect('/dashboard/services');

        $this->assertModelMissing($service);
        $this->assertDatabaseCount('service_packages', 0);
        Storage::disk('public')->assertMissing('services/hapus.png');
    }

    public function test_published_service_cannot_be_deleted_before_withdrawal(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $service = Service::factory()->published()->withPackages()->for($freelancer, 'freelancer')->create();

        $this->actingAs($freelancer)
            ->delete("/dashboard/services/{$service->id}")
            ->assertSessionHasErrors('service');

        $this->assertModelExists($service);
    }
}
