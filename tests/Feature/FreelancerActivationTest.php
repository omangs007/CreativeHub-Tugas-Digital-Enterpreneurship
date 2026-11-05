<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelancerActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_activate_freelancer_role(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post('/profile/freelancer')
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $customer->refresh();

        $this->assertTrue($customer->hasRole(RoleName::Customer));
        $this->assertTrue($customer->hasRole(RoleName::Freelancer));
        $this->assertNotNull($customer->freelancerProfile);
    }

    public function test_freelancer_activation_cannot_be_repeated(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)->post('/profile/freelancer')->assertForbidden();

        $this->assertSame(1, FreelancerProfile::where('user_id', $freelancer->id)->count());
    }

    public function test_freelancer_activation_ignores_role_input(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post('/profile/freelancer', ['role' => 'admin']);

        $this->assertFalse($customer->refresh()->isAdmin());
    }

    public function test_profile_page_shows_account_roles(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Customer')
            ->assertSee('Aktifkan Akun Freelancer');
    }
}
