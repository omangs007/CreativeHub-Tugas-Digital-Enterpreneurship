<?php

namespace Tests\Feature\Freelancer;

use App\Enums\FreelancerAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelancerProfileEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard/freelancer-profile')->assertRedirect(route('login'));
    }

    public function test_customer_without_freelancer_role_is_forbidden(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/dashboard/freelancer-profile')->assertForbidden();
        $this->actingAs($customer)->patch('/dashboard/freelancer-profile', ['headline' => 'x'])->assertForbidden();
    }

    public function test_freelancer_can_view_edit_form(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)
            ->get('/dashboard/freelancer-profile')
            ->assertOk()
            ->assertSee($freelancer->freelancerProfile->headline);
    }

    public function test_freelancer_can_update_profile(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)
            ->patch('/dashboard/freelancer-profile', [
                'headline' => 'Desainer Poster Event',
                'bio' => 'Berpengalaman 3 tahun.',
                'skills' => 'Photoshop,  Illustrator, photoshop, , Figma',
                'availability' => 'busy',
                'portfolio_url' => 'https://behance.net/contoh',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/freelancer-profile');

        $profile = $freelancer->freelancerProfile->fresh();

        $this->assertSame('Desainer Poster Event', $profile->headline);
        $this->assertSame(['Photoshop', 'Illustrator', 'Figma'], $profile->skills);
        $this->assertSame(FreelancerAvailability::Busy, $profile->availability);
        $this->assertSame('https://behance.net/contoh', $profile->portfolio_url);
    }

    public function test_profile_update_is_validated(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)
            ->patch('/dashboard/freelancer-profile', [
                'headline' => str_repeat('a', 121),
                'availability' => 'liburan',
                'portfolio_url' => 'javascript:alert(1)',
                'skills' => implode(',', range(1, 11)),
            ])
            ->assertSessionHasErrors(['headline', 'availability', 'portfolio_url', 'skills']);
    }

    public function test_profile_update_cannot_change_owner(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $other = User::factory()->freelancer()->create();

        $this->actingAs($freelancer)->patch('/dashboard/freelancer-profile', [
            'headline' => 'Judul Baru',
            'availability' => 'available',
            'user_id' => $other->id,
        ]);

        $this->assertSame($freelancer->id, $freelancer->freelancerProfile->fresh()->user_id);
        $this->assertNotSame('Judul Baru', $other->freelancerProfile->fresh()->headline);
    }
}
