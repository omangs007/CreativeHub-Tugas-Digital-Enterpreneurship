<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use App\Services\FinancialReport;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_consistent_demo_data_idempotently(): void
    {
        Storage::fake('public');
        config(['creativehub.commission_rate_bps' => 1000]);

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(7, User::where('email', 'like', '%@demo.creativehub.test')->count());
        $this->assertTrue(User::where('email', 'admin@demo.creativehub.test')->sole()->isAdmin());

        $this->assertSame(7, Order::count());
        $statuses = Order::pluck('status')->map(fn ($s) => $s->value)->sort()->values()->all();
        $this->assertSame(['cancelled', 'completed', 'delivered', 'in_progress', 'in_progress', 'pending_payment', 'pending_payment'], $statuses);

        $this->assertSame(4, Payment::where('status', PaymentStatus::Success)->count());
        $this->assertSame(1, Payment::where('status', PaymentStatus::Pending)->count());
        $this->assertSame(1, Payment::where('status', PaymentStatus::Failed)->count());
        $this->assertSame(1, Payment::where('status', PaymentStatus::Cancelled)->count());
        $this->assertTrue(Payment::where('status', PaymentStatus::Pending)->sole()->expires_at->isFuture());
        $this->assertSame(1, Review::count());
        $this->assertSame(1, Project::where('status', ProjectStatus::InProgress)->count());
        $this->assertSame(1, Project::where('status', ProjectStatus::Open)->count());

        // Setiap pesanan konsisten: total = komisi + freelancer, dan pembayaran berhasil sama dengan total.
        foreach (Order::with('successfulPayment')->get() as $order) {
            $this->assertSame($order->total_amount, $order->commission_amount + $order->freelancer_amount);
            if ($order->successfulPayment) {
                $this->assertSame($order->total_amount, $order->successfulPayment->amount);
                $this->assertNotSame(OrderStatus::PendingPayment, $order->status);
            }
        }

        // Transaksi tersebar di bulan lalu dan bulan ini (untuk demo filter laporan);
        // gabungan kedua bulan sama dengan seluruh pembayaran berhasil.
        $lastMonth = FinancialReport::fromInput(['preset' => 'month', 'month' => now()->subMonthNoOverflow()->format('Y-m')])->summary();
        $thisMonth = FinancialReport::fromInput(['preset' => 'this_month'])->summary();

        $this->assertGreaterThan(0, $lastMonth['revenue']);
        $this->assertGreaterThan(0, $thisMonth['success_count'] + $thisMonth['pending_count']);
        $this->assertSame(
            (int) Payment::where('status', PaymentStatus::Success)->sum('amount'),
            $lastMonth['revenue'] + $thisMonth['revenue'],
        );
        $this->assertGreaterThan(3, Payment::selectRaw('distinct date(coalesce(paid_at, created_at)) as d')->get()->count());
    }
}
