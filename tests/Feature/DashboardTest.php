<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProposalStatus;
use App\Models\Order;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/dashboard/earnings')->assertRedirect(route('login'));
    }

    public function test_customer_dashboard_shows_own_actions_only(): void
    {
        $customer = User::factory()->customer()->create();
        Order::factory()->total(250_000)->create(['customer_id' => $customer->id, 'title' => 'Pesanan Belum Dibayar Saya']);
        Order::factory()->status(OrderStatus::Delivered)->create(['customer_id' => $customer->id, 'title' => 'Pesanan Siap Ditinjau']);
        Order::factory()->create(['title' => 'Pesanan Milik Orang Lain']);
        $project = Project::factory()->for($customer, 'customer')->create(['title' => 'Proyek Terbuka Saya']);
        Proposal::factory()->count(2)->for($project)->create();

        $this->actingAs($customer)->get('/dashboard')
            ->assertOk()
            ->assertSee('Sebagai customer')
            ->assertDontSee('Sebagai freelancer')
            ->assertSee('Pesanan Belum Dibayar Saya')
            ->assertSee('Bayar Rp250.000')
            ->assertSee('Pesanan Siap Ditinjau')
            ->assertSee('Proyek Terbuka Saya')
            ->assertSee('2 penawaran baru')
            ->assertDontSee('Pesanan Milik Orang Lain');
    }

    public function test_freelancer_dashboard_shows_earnings_and_work(): void
    {
        config(['creativehub.commission_rate_bps' => 1000]);
        $freelancer = User::factory()->freelancer()->create();

        Order::factory()->total(1_000_000)->status(OrderStatus::Completed)->create(['freelancer_id' => $freelancer->id]);
        Order::factory()->total(500_000)->status(OrderStatus::InProgress)->create(['freelancer_id' => $freelancer->id, 'title' => 'Map Untuk Dikirim']);
        Order::factory()->total(300_000)->create(['freelancer_id' => $freelancer->id]); // belum dibayar: tidak dihitung
        Order::factory()->total(9_000_000)->status(OrderStatus::Completed)->create(); // milik freelancer lain
        Proposal::factory()->for($freelancer, 'freelancer')->create();
        Proposal::factory()->for($freelancer, 'freelancer')->status(ProposalStatus::Rejected)->create();

        // Saldo dompet berasal dari escrow pembayaran terverifikasi (lihat WalletTest);
        // order factory tanpa pembayaran tidak menghasilkan saldo.
        $this->actingAs($freelancer)->get('/dashboard')
            ->assertOk()
            ->assertSee('Sebagai freelancer')
            ->assertSee('Saldo tersedia')
            ->assertSee('Saldo tertahan')
            ->assertSee('Map Untuk Dikirim');

        // Nilai pesanan dihitung penuh; komisi baru dipotong saat penarikan.
        $this->actingAs($freelancer)->get('/dashboard/earnings')
            ->assertOk()
            ->assertSee('Rp1.000.000')
            ->assertSee('Rp500.000')
            ->assertSee('pencatatan simulasi')
            ->assertSee('Komisi platform dipotong saat penarikan saldo')
            ->assertDontSee('Rp900.000')
            ->assertDontSee('Rp9.000.000');
    }

    public function test_user_with_both_roles_sees_both_sections(): void
    {
        $user = User::factory()->freelancer()->create();

        $this->actingAs($user)->get('/dashboard')->assertSee('Sebagai customer')->assertSee('Sebagai freelancer');
    }

    public function test_earnings_page_requires_freelancer_role(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/dashboard/earnings')->assertForbidden();
    }

    public function test_admin_dashboard_links_to_admin_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')
            ->assertOk()
            ->assertSee('Buka Panel Admin');
    }
}
