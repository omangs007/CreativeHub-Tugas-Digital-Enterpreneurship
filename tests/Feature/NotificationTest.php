<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Services\OrderPlacement;
use App\Services\OrderWorkflow;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function titles(User $user): array
    {
        return $user->fresh()->notifications()->get()->pluck('data.title')->all();
    }

    public function test_order_lifecycle_notifies_the_other_party(): void
    {
        $order = Order::factory()->total(500_000)->create();
        $payments = app(PaymentProcessor::class);
        $payments->applyResult($payments->start($order, PaymentMethod::Qris, $order->customer), PaymentStatus::Success, $order->customer);

        $this->assertContains('Pesanan baru dibayar', $this->titles($order->freelancer));

        $workflow = app(OrderWorkflow::class);
        $workflow->deliver($order->fresh(), $order->freelancer, 'Hasil pertama', 'https://contoh.test/hasil', null);
        $this->assertContains('Hasil pekerjaan dikirim', $this->titles($order->customer));

        $workflow->requestRevision($order->fresh(), $order->customer, 'Tolong ubah warna');
        $this->assertContains('Revisi diminta', $this->titles($order->freelancer));

        $workflow->deliver($order->fresh(), $order->freelancer, 'Hasil revisi', 'https://contoh.test/hasil-2', null);
        $workflow->complete($order->fresh(), $order->customer);
        $this->assertContains('Pesanan selesai', $this->titles($order->freelancer));

        // Pelaku sendiri tidak menerima notifikasi atas aksinya.
        $this->assertNotContains('Revisi diminta', $this->titles($order->customer));
    }

    public function test_proposal_events_notify_customer_and_freelancers(): void
    {
        $project = Project::factory()->create();
        $winner = User::factory()->freelancer()->create();
        $loser = User::factory()->freelancer()->create();

        $this->actingAs($winner)->post("/dashboard/projects/{$project->id}/proposals", [
            'price' => '800000', 'delivery_days' => 7, 'revisions' => 2,
            'message' => 'Penawaran lengkap dengan pengalaman relevan untuk proyek ini.',
        ]);
        $this->assertContains('Penawaran baru', $this->titles($project->customer));

        $winning = Proposal::where('freelancer_id', $winner->id)->sole();
        Proposal::factory()->for($project)->for($loser, 'freelancer')->create();

        app(OrderPlacement::class)->acceptProposal($winning, $project->customer);

        $this->assertContains('Penawaran diterima', $this->titles($winner));
        $this->assertContains('Penawaran tidak dipilih', $this->titles($loser));
        $this->assertNotContains('Penawaran tidak dipilih', $this->titles($winner));
    }

    public function test_service_moderation_notifies_freelancer(): void
    {
        $admin = User::factory()->admin()->create();
        $approved = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();
        $rejected = Service::factory()->status(ServiceStatus::PendingReview)->withPackages()->create();

        $approved->approve($admin);
        $rejected->reject($admin, 'Deskripsi kurang lengkap');

        $this->assertContains('Layanan disetujui', $this->titles($approved->freelancer));
        $this->assertContains('Layanan ditolak', $this->titles($rejected->freelancer));
    }

    public function test_bell_shows_unread_count_and_notifications_page_lists_own_only(): void
    {
        $user = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        ActivityNotification::send($user, 'Judul Milik Saya', 'Isi notifikasi', 'orders.index');
        ActivityNotification::send($user, 'Judul Kedua', 'Isi kedua', 'orders.index');
        ActivityNotification::send($other, 'Notifikasi Orang Lain', 'Rahasia', 'orders.index');

        $this->actingAs($user)->get('/dashboard')->assertSee('Notifikasi, 2 belum dibaca');

        $this->actingAs($user)->get('/dashboard/notifications')
            ->assertOk()
            ->assertSee('Judul Milik Saya')
            ->assertDontSee('Notifikasi Orang Lain');
    }

    public function test_opening_notification_marks_read_and_redirects_internally(): void
    {
        $user = User::factory()->customer()->create();
        ActivityNotification::send($user, 'Buka Saya', 'Isi', 'orders.index');
        $notification = $user->notifications()->sole();

        $this->actingAs($user)->get("/dashboard/notifications/{$notification->id}")->assertRedirect('/dashboard/orders');
        $this->assertNotNull($notification->fresh()->read_at);

        // Path eksternal (tidak pernah dibuat aplikasi) tidak diikuti.
        $user->notify(new ActivityNotification('Tautan eksternal', 'Isi', '//evil.test/phish'));
        $evil = $user->notifications()->whereNull('read_at')->sole();
        $this->actingAs($user)->get("/dashboard/notifications/{$evil->id}")->assertRedirect(route('notifications.index'));
    }

    public function test_user_cannot_open_someone_elses_notification(): void
    {
        $owner = User::factory()->customer()->create();
        ActivityNotification::send($owner, 'Milik Owner', 'Isi', 'orders.index');
        $notification = $owner->notifications()->sole();

        $this->actingAs(User::factory()->customer()->create())->get("/dashboard/notifications/{$notification->id}")->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read(): void
    {
        $user = User::factory()->customer()->create();
        ActivityNotification::send($user, 'Satu', 'Isi', 'orders.index');
        ActivityNotification::send($user, 'Dua', 'Isi', 'orders.index');

        $this->actingAs($user)->post('/dashboard/notifications/read-all')->assertRedirect('/dashboard/notifications');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_unread_chat_messages_show_in_navigation(): void
    {
        $order = Order::factory()->status(OrderStatus::InProgress)->create();
        $conversation = Conversation::forOrder($order);
        $this->actingAs($order->freelancer)->postJson("/dashboard/messages/{$conversation->id}", ['body' => 'Halo customer']);

        $this->assertSame(1, Message::whereNull('read_at')->count());
        $this->actingAs($order->customer)->get('/dashboard')->assertSee('Pesan<span', false);
    }
}
