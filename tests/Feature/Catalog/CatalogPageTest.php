<?php

namespace Tests\Feature\Catalog;

use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_homepage_with_categories_and_published_services(): void
    {
        $category = Category::factory()->create(['name' => 'Roblox Map']);
        Service::factory()->published()->withPackages()->for($category)->create(['title' => 'Map Obby Keren']);

        $this->get('/')
            ->assertOk()
            ->assertSee('CreativeHub')
            ->assertSee('Roblox Map')
            ->assertSee('Map Obby Keren');
    }

    public function test_homepage_shows_empty_state_without_services(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Belum ada layanan yang dipublikasikan');
    }

    public function test_guest_can_view_catalog_with_starting_price(): void
    {
        Service::factory()->published()->withPackages(150_000)->create(['title' => 'Website Company Profile']);

        $this->get('/services')
            ->assertOk()
            ->assertSee('Website Company Profile')
            ->assertSee('Rp150.000');
    }

    public function test_catalog_only_lists_published_services(): void
    {
        Service::factory()->published()->withPackages()->create(['title' => 'Layanan Published']);
        Service::factory()->status(ServiceStatus::Draft)->withPackages()->create(['title' => 'Layanan Draft']);
        Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create(['title' => 'Layanan Pending']);
        Service::factory()->status(ServiceStatus::Rejected)->withPackages()->create(['title' => 'Layanan Ditolak']);

        $this->get('/services')
            ->assertOk()
            ->assertSee('Layanan Published')
            ->assertDontSee('Layanan Draft')
            ->assertDontSee('Layanan Pending')
            ->assertDontSee('Layanan Ditolak');
    }

    public function test_catalog_hides_services_in_inactive_categories(): void
    {
        $inactive = Category::factory()->inactive()->create();
        Service::factory()->published()->withPackages()->for($inactive)->create(['title' => 'Layanan Kategori Nonaktif']);

        $this->get('/services')->assertOk()->assertDontSee('Layanan Kategori Nonaktif');
    }

    public function test_catalog_hides_services_without_active_packages(): void
    {
        Service::factory()->published()->create(['title' => 'Layanan Tanpa Paket']);

        $this->get('/services')->assertOk()->assertDontSee('Layanan Tanpa Paket');
    }

    public function test_catalog_search_matches_title_case_insensitively(): void
    {
        Service::factory()->published()->withPackages()->create(['title' => 'Desain Poster Konser']);
        Service::factory()->published()->withPackages()->create(['title' => 'Landing Page Toko Online']);

        $this->get('/services?q=poster')
            ->assertOk()
            ->assertSee('Desain Poster Konser')
            ->assertDontSee('Landing Page Toko Online');
    }

    public function test_catalog_can_be_filtered_by_category(): void
    {
        $roblox = Category::factory()->create(['slug' => 'roblox-map']);
        $website = Category::factory()->create(['slug' => 'website-development']);
        Service::factory()->published()->withPackages()->for($roblox)->create(['title' => 'Map Tycoon']);
        Service::factory()->published()->withPackages()->for($website)->create(['title' => 'Website Sekolah']);

        $this->get('/services?kategori=roblox-map')
            ->assertOk()
            ->assertSee('Map Tycoon')
            ->assertDontSee('Website Sekolah');
    }

    public function test_catalog_shows_empty_state_when_nothing_matches(): void
    {
        $this->get('/services?q=tidak-ada-yang-cocok')
            ->assertOk()
            ->assertSee('Tidak ada layanan yang cocok');
    }

    public function test_catalog_is_paginated(): void
    {
        Service::factory()->count(13)->published()->withPackages()->create();

        $this->get('/services')->assertOk()->assertViewHas('services', fn ($services) => $services->count() === 12);
        $this->get('/services?page=2')->assertOk()->assertViewHas('services', fn ($services) => $services->count() === 1);
    }

    public function test_guest_can_view_category_page(): void
    {
        $category = Category::factory()->create(['name' => 'Poster & Graphic Design', 'slug' => 'poster-graphic-design']);
        $other = Category::factory()->create();
        Service::factory()->published()->withPackages()->for($category)->create(['title' => 'Poster Event Kampus']);
        Service::factory()->published()->withPackages()->for($other)->create(['title' => 'Layanan Kategori Lain']);

        $this->get('/categories/poster-graphic-design')
            ->assertOk()
            ->assertSee('Poster &amp; Graphic Design', false)
            ->assertSee('Poster Event Kampus')
            ->assertDontSee('Layanan Kategori Lain');
    }

    public function test_inactive_or_unknown_category_page_returns_404(): void
    {
        Category::factory()->inactive()->create(['slug' => 'kategori-nonaktif']);

        $this->get('/categories/kategori-nonaktif')->assertNotFound();
        $this->get('/categories/tidak-ada')->assertNotFound();
    }
}
