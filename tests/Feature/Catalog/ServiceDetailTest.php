<?php

namespace Tests\Feature\Catalog;

use App\Enums\PackageTier;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_published_service_detail_with_packages(): void
    {
        $freelancer = User::factory()->freelancer()->create(['name' => 'Rina Kreatif']);
        $service = Service::factory()->published()->withPackages(250_000)->for($freelancer, 'freelancer')
            ->create(['title' => 'Map Roblox Horor', 'slug' => 'map-roblox-horor']);

        $this->get('/services/map-roblox-horor')
            ->assertOk()
            ->assertSee('Map Roblox Horor')
            ->assertSee('Rina Kreatif')
            ->assertSee($service->category->name)
            ->assertSeeInOrder(['Basic', 'Standard', 'Premium'])
            ->assertSee('Rp250.000')
            ->assertSee('Rp500.000')
            ->assertSee('Rp1.000.000');
    }

    public function test_detail_only_shows_active_packages(): void
    {
        $service = Service::factory()->published()->create(['slug' => 'layanan-paket-campur']);
        ServicePackage::factory()->for($service)->create(['tier' => PackageTier::Basic, 'name' => 'Paket Hemat', 'price' => 100_000]);
        ServicePackage::factory()->for($service)->inactive()->create(['tier' => PackageTier::Premium, 'name' => 'Paket Nonaktif', 'price' => 900_000]);

        $this->get('/services/layanan-paket-campur')
            ->assertOk()
            ->assertSee('Paket Hemat')
            ->assertDontSee('Paket Nonaktif')
            ->assertDontSee('Rp900.000');
    }

    public function test_non_published_service_detail_returns_404(): void
    {
        foreach ([ServiceStatus::Draft, ServiceStatus::PendingReview, ServiceStatus::Rejected] as $status) {
            $service = Service::factory()->status($status)->withPackages()->create();

            $this->get("/services/{$service->slug}")->assertNotFound();
        }
    }

    public function test_service_in_inactive_category_returns_404(): void
    {
        $service = Service::factory()->published()->withPackages()
            ->for(Category::factory()->inactive())
            ->create();

        $this->get("/services/{$service->slug}")->assertNotFound();
    }

    public function test_unknown_service_returns_404(): void
    {
        $this->get('/services/tidak-ada')->assertNotFound();
    }
}
