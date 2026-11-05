<?php

namespace Database\Seeders;

use App\Enums\PackageTier;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Order;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderPlacement;
use App\Services\OrderWorkflow;
use App\Services\PaymentProcessor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DATA DEMO lengkap untuk presentasi (bukan data produksi).
 *
 * Jalankan: php artisan db:seed --class=DemoSeeder
 *
 * Membuat admin demo, customer demo, proyek + penawaran, serta pesanan dan pembayaran
 * simulasi dengan berbagai status pada tanggal berbeda (untuk demo filter laporan).
 * Transaksi dibuat lewat alur aplikasi (OrderPlacement, PaymentProcessor, OrderWorkflow)
 * agar snapshot, komisi, riwayat status, dan log pembayaran konsisten.
 * Password akun demo dibuat acak dan hanya ditampilkan saat akun dibuat.
 */
class DemoSeeder extends Seeder
{
    public function __construct(
        private readonly OrderPlacement $placement,
        private readonly PaymentProcessor $payments,
        private readonly OrderWorkflow $workflow,
    ) {}

    public function run(): void
    {
        $this->call(CatalogDemoSeeder::class);

        $this->demoUser('admin', 'Admin Demo CreativeHub', [RoleName::Admin]);
        $budi = $this->demoUser('budi', 'Budi Santoso', [RoleName::Customer]);
        $siti = $this->demoUser('siti', 'Siti Rahmawati', [RoleName::Customer]);
        $andi = $this->demoUser('andi', 'Andi Wijaya', [RoleName::Customer]);

        if (Order::where('customer_id', $budi->id)->exists()) {
            $this->command?->info('  Transaksi demo sudah ada, dilewati.');

            return;
        }

        $raka = User::where('email', 'raka@'.CatalogDemoSeeder::EMAIL_DOMAIN)->firstOrFail();
        $nadia = User::where('email', 'nadia@'.CatalogDemoSeeder::EMAIL_DOMAIN)->firstOrFail();
        $dimas = User::where('email', 'dimas@'.CatalogDemoSeeder::EMAIL_DOMAIN)->firstOrFail();
        $now = CarbonImmutable::now();

        DB::transaction(function () use ($budi, $siti, $andi, $raka, $nadia, $dimas, $now) {
            // 1. Selesai + ulasan (bulan lalu).
            $order = $this->packageOrder($budi, 'desain-map-obby-roblox-custom', PackageTier::Premium, 'Tema luar angkasa, warna ungu.');
            $this->pay($order, $budi, PaymentMethod::Qris, PaymentStatus::Success);
            $this->workflow->deliver($order, $raka, 'Map 50 stage sudah selesai beserta gamepass skip stage.', 'https://www.roblox.com/', null);
            $this->workflow->complete($order->fresh(), $budi);
            $this->review($order, 5, 'Mapnya keren dan rapi, anak-anak suka sekali!');
            $this->backdate($order, $now->subMonthNoOverflow()->setDay(20)->setTime(10, 15));

            // 2. Sedang dikerjakan.
            $order = $this->packageOrder($siti, 'landing-page-responsif-untuk-umkm', PackageTier::Standard, 'Untuk toko kue rumahan, warna pastel.');
            $this->pay($order, $siti, PaymentMethod::VirtualAccount, PaymentStatus::Success);
            $this->backdate($order, $now->subDays(10)->setTime(14, 5));

            // 3. Hasil dikirim, menunggu ditinjau customer.
            $order = $this->packageOrder($andi, 'desain-poster-event-promosi', PackageTier::Basic, 'Poster lomba futsal antar kelas.');
            $this->pay($order, $andi, PaymentMethod::Ewallet, PaymentStatus::Success);
            $this->workflow->deliver($order, $dimas, 'Poster A4 siap cetak dan versi Instagram.', 'https://drive.google.com/', null);
            $this->backdate($order, $now->subDays(3)->setTime(9, 30));

            // 4. Menunggu pembayaran (tagihan pending).
            $order = $this->packageOrder($budi, 'paket-desain-feed-instagram', PackageTier::Basic, null);
            $this->pay($order, $budi, PaymentMethod::Qris, PaymentStatus::Pending);
            $this->backdate($order, $now->subDays(2)->setTime(19, 45));

            // 5. Pembayaran gagal, pesanan masih menunggu pembayaran.
            $order = $this->packageOrder($siti, 'website-company-profile-lengkap', PackageTier::Basic, null);
            $this->pay($order, $siti, PaymentMethod::VirtualAccount, PaymentStatus::Failed);
            $this->backdate($order, $now->subDays(5)->setTime(11, 0));

            // 6. Dibatalkan customer sebelum dibayar.
            $order = $this->packageOrder($andi, 'map-roleplay-kota-untuk-roblox', PackageTier::Basic, null);
            $this->payments->start($order, PaymentMethod::Qris, $andi);
            $this->workflow->cancelUnpaid($order, $andi);
            $this->backdate($order, $now->subDays(7)->setTime(16, 20));

            // 7. Proyek: dua penawaran, satu diterima, dibayar, dikerjakan.
            $project = $this->project($siti, 'roblox-map', 'Map Roblox untuk event ulang tahun sekolah', 'Butuh map dengan panggung, area lomba lari, dan spawn untuk 40 pemain. Tema warna sekolah biru putih.', 500_000, 1_500_000);
            $accepted = $this->proposal($project, $raka, 1_200_000, 12, 'Saya sudah membuat beberapa map event sekolah. Termasuk panggung, area lomba, dan uji main bersama tim Anda.');
            $this->proposal($project, $dimas, 900_000, 10, 'Saya bisa bantu dari sisi desain visual dan dekorasi map agar tampil meriah.');
            $order = $this->placement->acceptProposal($accepted, $siti);
            $this->pay($order, $siti, PaymentMethod::Qris, PaymentStatus::Success);
            $this->backdate($order, $now->subDay()->setTime(8, 10));

            // 8. Proyek terbuka dengan penawaran yang menunggu keputusan.
            $project = $this->project($andi, 'website-development', 'Website profil komunitas fotografi kampus', 'Website sederhana berisi profil, galeri foto, dan jadwal kegiatan. Bisa diperbarui oleh pengurus.', null, 2_000_000);
            $this->proposal($project, $nadia, 1_750_000, 14, 'Saya akan membuat website responsif dengan panel admin sederhana untuk galeri dan jadwal.');
        });

        $this->command?->info('  Data transaksi demo dibuat.');
    }

    /**
     * @param  list<RoleName>  $roles
     */
    private function demoUser(string $username, string $name, array $roles): User
    {
        $email = $username.'@'.CatalogDemoSeeder::EMAIL_DOMAIN;
        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = Str::password(16, symbols: false);
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->command?->line("  Akun demo: {$email}  password: {$password}");
        }

        foreach ($roles as $role) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function packageOrder(User $customer, string $serviceSlug, PackageTier $tier, ?string $note): Order
    {
        $package = Service::where('slug', $serviceSlug)->firstOrFail()->packages()->where('tier', $tier)->firstOrFail();

        return $this->placement->orderPackage($package, $customer, $note);
    }

    private function pay(Order $order, User $customer, PaymentMethod $method, PaymentStatus $result): void
    {
        $payment = $this->payments->start($order, $method, $customer);

        if ($result !== PaymentStatus::Pending) {
            $this->payments->applyResult($payment, $result, $customer);
        }
    }

    private function review(Order $order, int $rating, string $comment): void
    {
        $review = new Review;
        $review->order_id = $order->id;
        $review->service_id = $order->service_id;
        $review->customer_id = $order->customer_id;
        $review->freelancer_id = $order->freelancer_id;
        $review->rating = $rating;
        $review->comment = $comment;
        $review->save();
    }

    private function project(User $customer, string $categorySlug, string $title, string $description, ?int $min, ?int $max): Project
    {
        $project = new Project([
            'category_id' => Category::where('slug', $categorySlug)->value('id'),
            'title' => $title,
            'description' => $description,
            'budget_min' => $min,
            'budget_max' => $max,
            'deadline' => now()->addWeeks(4)->toDateString(),
        ]);
        $project->customer_id = $customer->id;
        $project->save();

        return $project;
    }

    private function proposal(Project $project, User $freelancer, int $price, int $days, string $message): Proposal
    {
        $proposal = new Proposal(['price' => $price, 'delivery_days' => $days, 'revisions' => 2, 'message' => $message]);
        $proposal->project_id = $project->id;
        $proposal->freelancer_id = $freelancer->id;
        $proposal->status = ProposalStatus::Pending;
        $proposal->save();

        return $proposal;
    }

    /**
     * Geser seluruh jejak waktu satu pesanan (order, riwayat, pembayaran, event, hasil, ulasan)
     * ke tanggal demo, dengan urutan kejadian tetap terjaga.
     */
    private function backdate(Order $order, CarbonImmutable $at): void
    {
        $order->refresh();
        $step = fn (int $minutes) => $at->addMinutes($minutes)->toDateTimeString();

        DB::table('orders')->where('id', $order->id)->update([
            'created_at' => $step(0),
            'updated_at' => $step(90),
            'paid_at' => $order->paid_at ? $step(10) : null,
            'delivered_at' => $order->delivered_at ? $step(60) : null,
            'completed_at' => $order->completed_at ? $step(90) : null,
            'cancelled_at' => $order->cancelled_at ? $step(30) : null,
        ]);

        foreach (DB::table('order_status_histories')->where('order_id', $order->id)->orderBy('id')->pluck('id')->values() as $i => $id) {
            DB::table('order_status_histories')->where('id', $id)->update(['created_at' => $step($i * 10)]);
        }

        foreach (DB::table('payments')->where('order_id', $order->id)->get() as $payment) {
            DB::table('payments')->where('id', $payment->id)->update([
                'created_at' => $step(5),
                'updated_at' => $step(10),
                // Tagihan pending tetap bisa disimulasikan saat demo.
                'expires_at' => $payment->status === PaymentStatus::Pending->value
                    ? now()->addDay()->toDateTimeString()
                    : $at->addMinutes(5)->addDay()->toDateTimeString(),
                'paid_at' => $payment->paid_at ? $step(10) : null,
                'failed_at' => $payment->failed_at ? $step(10) : null,
            ]);
            DB::table('payment_events')->where('payment_id', $payment->id)->update(['created_at' => $step(10)]);
        }

        DB::table('order_deliveries')->where('order_id', $order->id)->update(['created_at' => $step(60), 'updated_at' => $step(60)]);
        DB::table('reviews')->where('order_id', $order->id)->update(['created_at' => $step(100), 'updated_at' => $step(100)]);
    }
}
