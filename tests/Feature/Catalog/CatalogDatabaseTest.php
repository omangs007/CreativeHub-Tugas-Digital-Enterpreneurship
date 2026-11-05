<?php

namespace Tests\Feature\Catalog;

use App\Enums\PackageTier;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Database\Seeders\CatalogDemoSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_seeder_creates_required_categories_idempotently(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(
            ['roblox-map', 'website-development', 'poster-graphic-design'],
            Category::active()->pluck('slug')->all(),
        );
    }

    public function test_catalog_demo_seeder_is_idempotent_and_keeps_drafts_hidden(): void
    {
        Storage::fake('public');

        $this->seed(CatalogDemoSeeder::class);
        $this->seed(CatalogDemoSeeder::class);

        $this->assertSame(3, User::where('email', 'like', '%@'.CatalogDemoSeeder::EMAIL_DOMAIN)->count());
        $this->assertSame(7, Service::count());
        $this->assertSame(6, Service::publiclyVisible()->count());
        $this->assertSame(6, PortfolioItem::count());
        PortfolioItem::each(fn (PortfolioItem $item) => Storage::disk('public')->assertExists($item->image_path));

        $this->get('/services')->assertOk()->assertDontSee('Desain Logo Minimalis');
    }

    public function test_service_slug_must_be_unique(): void
    {
        Service::factory()->create(['slug' => 'slug-sama']);

        $this->expectException(QueryException::class);

        Service::factory()->create(['slug' => 'slug-sama']);
    }

    public function test_service_can_only_have_one_package_per_tier(): void
    {
        $service = Service::factory()->create();
        ServicePackage::factory()->for($service)->create(['tier' => PackageTier::Basic]);

        $this->expectException(QueryException::class);

        ServicePackage::factory()->for($service)->create(['tier' => PackageTier::Basic]);
    }

    public function test_package_price_must_be_positive(): void
    {
        $this->expectException(QueryException::class);

        ServicePackage::factory()->create(['price' => 0]);
    }

    public function test_service_status_is_restricted_to_known_values(): void
    {
        $service = Service::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('services')->where('id', $service->id)->update(['status' => 'hacked']);
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $service = Service::factory()->create();

        $service->fill(['status' => 'published', 'title' => 'Judul Baru']);

        $this->assertSame('Judul Baru', $service->title);
        $this->assertSame('draft', $service->status->value);
    }
}
