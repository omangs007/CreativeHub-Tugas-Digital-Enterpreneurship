<?php

namespace Tests\Feature\Catalog;

use App\Enums\PackageTier;
use App\Models\Service;
use App\Models\ServicePackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Service::factory()->published()->withPackages(50_000)->create(['title' => 'Layanan Murah']);
        Service::factory()->published()->withPackages(300_000)->create(['title' => 'Layanan Sedang']);
        Service::factory()->published()->withPackages(1_000_000)->create(['title' => 'Layanan Mahal']);
    }

    public function test_filter_by_minimum_and_maximum_starting_price(): void
    {
        $this->get('/services?harga_min=100000&harga_maks=500000')
            ->assertOk()
            ->assertSee('Layanan Sedang')
            ->assertDontSee('Layanan Murah')
            ->assertDontSee('Layanan Mahal');
    }

    public function test_starting_price_ignores_inactive_packages(): void
    {
        $service = Service::factory()->published()->create(['title' => 'Layanan Paket Murah Nonaktif']);
        ServicePackage::factory()->for($service)->inactive()->create(['tier' => PackageTier::Basic, 'price' => 20_000]);
        ServicePackage::factory()->for($service)->create(['tier' => PackageTier::Standard, 'price' => 800_000]);

        $this->get('/services?harga_maks=100000')
            ->assertOk()
            ->assertSee('Layanan Murah')
            ->assertDontSee('Layanan Paket Murah Nonaktif');
    }

    public function test_sort_by_price(): void
    {
        $this->get('/services?urut=termurah')->assertOk()->assertSeeInOrder(['Layanan Murah', 'Layanan Sedang', 'Layanan Mahal']);
        $this->get('/services?urut=termahal')->assertOk()->assertSeeInOrder(['Layanan Mahal', 'Layanan Sedang', 'Layanan Murah']);
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->get('/services?harga_min=abc&harga_maks=-5&urut=drop-table')
            ->assertOk()
            ->assertSee('Layanan Murah')
            ->assertSee('Layanan Mahal');
    }

    public function test_category_links_keep_price_filter(): void
    {
        $this->get('/services?harga_min=100000')
            ->assertOk()
            ->assertSee('harga_min=100000', false);
    }
}
