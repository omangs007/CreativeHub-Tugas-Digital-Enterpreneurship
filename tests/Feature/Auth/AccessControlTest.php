<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
        $this->post('/profile/freelancer')->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_to_admin_login_from_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_customers_cannot_access_admin_panel(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_freelancers_cannot_access_admin_panel(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)->get('/admin')->assertForbidden();
    }

    public function test_users_without_any_role_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admins_can_access_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_admin_link_is_only_shown_to_admins(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get('/dashboard')
            ->assertDontSee('Panel Admin');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertSee('Panel Admin');
    }
}
