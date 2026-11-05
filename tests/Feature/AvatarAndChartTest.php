<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Filament\Widgets\RevenueChart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\FinancialReport;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AvatarAndChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_user_can_upload_replace_and_remove_avatar(): void
    {
        $user = User::factory()->freelancer()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('Foto Saya.png', 400, 400)])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $first = $user->fresh()->avatar_path;
        $this->assertStringStartsWith("avatars/{$user->id}/", $first);
        Storage::disk('public')->assertExists($first);

        $this->actingAs($user)->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('baru.jpg', 300, 300)]);
        Storage::disk('public')->assertMissing($first);

        $this->get("/freelancers/{$user->id}")->assertSee($user->fresh()->avatarUrl());

        $this->actingAs($user)->delete('/profile/avatar')->assertRedirect('/profile');
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_avatar_is_validated(): void
    {
        $user = User::factory()->customer()->create();

        foreach ([
            UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
            UploadedFile::fake()->image('kecil.png', 50, 50),
            UploadedFile::fake()->image('besar.jpg', 400, 400)->size(1500),
            UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
        ] as $file) {
            $this->actingAs($user)->post('/profile/avatar', ['avatar' => $file])->assertSessionHasErrorsIn('avatar', 'avatar');
        }

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_avatar_cannot_be_set_through_profile_update(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->patch('/profile', ['name' => 'Nama Baru', 'email' => $user->email, 'avatar_path' => '../../.env']);

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_service_page_shows_freelancer_photo(): void
    {
        $service = Service::factory()->published()->withPackages()->create();
        $service->freelancer->forceFill(['avatar_path' => 'avatars/1/foto.png'])->save();

        $this->get("/services/{$service->slug}")->assertSee('/storage/avatars/1/foto.png');
    }

    public function test_daily_revenue_and_admin_chart(): void
    {
        Carbon::setTestNow('2026-11-15 10:00:00');

        foreach ([['2026-11-14 09:00:00', 100_000], ['2026-11-14 15:00:00', 50_000], ['2026-11-15 08:00:00', 70_000]] as [$at, $amount]) {
            Payment::factory()->for(Order::factory()->total($amount))->create(['status' => PaymentStatus::Success, 'paid_at' => $at, 'created_at' => $at]);
        }
        Payment::factory()->for(Order::factory()->total(999_000))->create(['status' => PaymentStatus::Failed, 'created_at' => '2026-11-14 10:00:00']);

        $daily = FinancialReport::fromInput(['preset' => 'last_7_days'])->dailyRevenue();
        $this->assertCount(7, $daily);
        $this->assertSame(150_000, $daily['2026-11-14']);
        $this->assertSame(70_000, $daily['2026-11-15']);
        $this->assertSame(0, $daily['2026-11-09']);

        Filament::setCurrentPanel('admin');
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(RevenueChart::class)->assertOk()->assertSee('Omzet 30 hari terakhir');
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Omzet bulan ini')->assertSee('Rp220.000');

        Carbon::setTestNow();
    }
}
