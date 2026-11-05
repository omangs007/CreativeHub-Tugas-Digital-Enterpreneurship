<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:freelancer'])
            ->get('/_test/freelancer-only', fn () => 'ok');

        Route::middleware(['web', 'auth', 'role:freelancer,admin'])
            ->get('/_test/freelancer-or-admin', fn () => 'ok');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/_test/freelancer-only')->assertRedirect(route('login'));
    }

    public function test_user_without_required_role_is_forbidden(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/_test/freelancer-only')->assertForbidden();
    }

    public function test_user_with_required_role_is_allowed(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)->get('/_test/freelancer-only')->assertOk();
    }

    public function test_any_of_the_listed_roles_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/_test/freelancer-or-admin')->assertOk();
        $this->actingAs($admin)->get('/_test/freelancer-only')->assertForbidden();
    }
}
