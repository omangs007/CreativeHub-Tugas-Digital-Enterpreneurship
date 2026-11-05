<?php

namespace Tests\Feature\Freelancer;

use App\Enums\ServiceStatus;
use App\Models\PortfolioItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelancerPublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_freelancer_profile(): void
    {
        $freelancer = User::factory()->freelancer()->create(['name' => 'Nadia Putri']);
        $freelancer->freelancerProfile->update(['headline' => 'Web Developer Laravel', 'skills' => ['Laravel', 'Tailwind CSS']]);
        PortfolioItem::factory()->for($freelancer, 'freelancer')->create(['title' => 'Website Toko Kue']);
        Service::factory()->published()->withPackages()->for($freelancer, 'freelancer')->create(['title' => 'Landing Page UMKM']);
        Service::factory()->status(ServiceStatus::Draft)->withPackages()->for($freelancer, 'freelancer')->create(['title' => 'Layanan Masih Draf']);

        $this->get("/freelancers/{$freelancer->id}")
            ->assertOk()
            ->assertSee('Nadia Putri')
            ->assertSee('Web Developer Laravel')
            ->assertSee('Tailwind CSS')
            ->assertSee('Website Toko Kue')
            ->assertSee('Landing Page UMKM')
            ->assertDontSee('Layanan Masih Draf')
            ->assertDontSee($freelancer->email);
    }

    public function test_profile_shows_empty_states(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->get("/freelancers/{$freelancer->id}")
            ->assertOk()
            ->assertSee('Belum ada portofolio')
            ->assertSee('Belum ada layanan yang dipublikasikan');
    }

    public function test_non_freelancer_or_unknown_user_returns_404(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        $this->get("/freelancers/{$customer->id}")->assertNotFound();
        $this->get("/freelancers/{$admin->id}")->assertNotFound();
        $this->get('/freelancers/999999')->assertNotFound();
        $this->get('/freelancers/bukan-angka')->assertNotFound();
    }

    public function test_service_detail_links_to_freelancer_profile(): void
    {
        $service = Service::factory()->published()->withPackages()->create();

        $this->get("/services/{$service->slug}")
            ->assertOk()
            ->assertSee(route('freelancers.show', $service->user_id));
    }

    public function test_homepage_shows_featured_freelancers_with_published_services(): void
    {
        $active = User::factory()->freelancer()->create(['name' => 'Freelancer Aktif']);
        Service::factory()->published()->withPackages()->for($active, 'freelancer')->create();
        User::factory()->freelancer()->create(['name' => 'Freelancer Tanpa Layanan']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Freelancer unggulan')
            ->assertSee('Freelancer Aktif')
            ->assertSee(route('freelancers.show', $active))
            ->assertDontSee('Freelancer Tanpa Layanan');
    }
}
