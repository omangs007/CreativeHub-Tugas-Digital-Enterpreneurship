<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\User;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_every_dashboard_page(): void
    {
        foreach ([
            '/dashboard', '/profile', '/dashboard/orders', '/dashboard/payments', '/dashboard/messages',
            '/dashboard/projects', '/dashboard/projects/create', '/dashboard/services', '/dashboard/services/create',
            '/dashboard/proposals', '/dashboard/portfolio', '/dashboard/freelancer-profile', '/dashboard/earnings',
            '/admin/financial-reports',
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_foreign_records_are_not_accessible_by_id(): void
    {
        $order = Order::factory()->create();
        $payment = app(PaymentProcessor::class)->start($order, PaymentMethod::Qris, $order->customer);
        $conversation = Conversation::forOrder($order);
        $service = Service::factory()->withPackages()->create();
        $project = Project::factory()->create();
        $proposal = Proposal::factory()->create();
        $portfolio = PortfolioItem::factory()->create();

        // Penyerang memiliki peran customer + freelancer agar lolos middleware peran.
        $attacker = User::factory()->freelancer()->create();

        $checks = [
            ['get', "/dashboard/orders/{$order->id}"],
            ['get', "/checkout/{$order->id}"],
            ['post', "/checkout/{$order->id}/payments", ['method' => 'qris']],
            ['get', "/payments/{$payment->id}"],
            ['post', "/payments/{$payment->id}/simulate", ['result' => 'success']],
            ['get', "/dashboard/messages/{$conversation->id}"],
            ['get', "/dashboard/orders/{$order->id}/chat"],
            ['post', "/dashboard/orders/{$order->id}/cancel"],
            ['get', "/dashboard/services/{$service->id}/edit"],
            ['delete', "/dashboard/services/{$service->id}"],
            ['get', "/dashboard/projects/{$project->id}"],
            ['post', "/dashboard/projects/{$project->id}/close"],
            ['get', "/dashboard/proposals/{$proposal->id}/edit"],
            ['post', "/dashboard/proposals/{$proposal->id}/accept"],
            ['get', "/dashboard/portfolio/{$portfolio->id}/edit"],
            ['delete', "/dashboard/portfolio/{$portfolio->id}"],
        ];

        foreach ($checks as $check) {
            [$method, $url] = $check;
            $status = $this->actingAs($attacker)->{$method}($url, $check[2] ?? [])->getStatusCode();
            $this->assertContains($status, [403, 404], "{$method} {$url} seharusnya ditolak, tetapi {$status}");
        }

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_ignores_admin_intended_url_for_non_admin(): void
    {
        $customer = User::factory()->customer()->create();

        $this->get('/admin/financial-reports');
        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])->assertRedirect('/dashboard');

        auth()->logout();
        $admin = User::factory()->admin()->create();
        $this->get('/admin/financial-reports');
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin/financial-reports');
    }

    public function test_custom_error_pages_are_in_indonesian(): void
    {
        $this->get('/services/tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan');
        $this->actingAs(User::factory()->customer()->create())->get('/dashboard/services')->assertForbidden()->assertSee('Akses ditolak');
    }

    public function test_customer_can_cancel_unpaid_order(): void
    {
        $order = Order::factory()->create();
        $payment = app(PaymentProcessor::class)->start($order, PaymentMethod::VirtualAccount, $order->customer);

        $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/cancel")->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
        $this->assertSame(ProjectStatus::Closed, $order->project->status);

        // Tidak bisa dibatalkan lagi atau dibayar.
        $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/cancel")->assertForbidden();
        $this->actingAs($order->customer)->post("/checkout/{$order->id}/payments", ['method' => 'qris'])->assertForbidden();
    }

    public function test_paid_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->status(OrderStatus::InProgress)->create();

        $this->actingAs($order->customer)->post("/dashboard/orders/{$order->id}/cancel")->assertForbidden();
        $this->actingAs($order->freelancer)->post("/dashboard/orders/{$order->id}/cancel")->assertForbidden();

        $this->assertSame(OrderStatus::InProgress, $order->fresh()->status);
    }

    public function test_pagination_is_translated(): void
    {
        Service::factory()->count(13)->published()->withPackages()->create();

        $this->get('/services')->assertSee('Menampilkan')->assertDontSee('Showing');
    }
}
