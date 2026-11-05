<?php

namespace Tests\Feature\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $order = Order::factory()->total(1_250_000)->create(['title' => 'Website Company Profile Lengkap']);
        $order->recordHistory(null, OrderStatus::PendingPayment, $order->customer);

        $processor = app(PaymentProcessor::class);
        $payment = $processor->start($order, PaymentMethod::VirtualAccount, $order->customer);
        $this->payment = $processor->applyResult($payment, PaymentStatus::Success, $order->customer);
    }

    public function test_customer_sees_receipt_with_data_from_database(): void
    {
        $order = $this->payment->order;

        $this->actingAs($order->customer)
            ->get("/dashboard/receipts/{$this->payment->id}")
            ->assertOk()
            ->assertSee('STRUK PEMBAYARAN')
            ->assertSee('CreativeHub', false)
            ->assertSee($this->payment->transaction_code)
            ->assertSee($order->order_number)
            ->assertSee($order->customer->name)
            ->assertSee($order->freelancer->name)
            ->assertSee('Website Company Profile Lengkap')
            ->assertSee('Rp1.250.000')
            ->assertSee('Rp125.000')
            ->assertSee('Rp1.125.000')
            ->assertSee('Transfer Virtual Account (Simulasi)')
            ->assertSee('Berhasil')
            ->assertSee('simulasi')
            ->assertSee($this->payment->paid_at->translatedFormat('d F Y'))
            ->assertSee('Cetak Struk');
    }

    public function test_query_parameters_cannot_change_receipt_values(): void
    {
        $this->actingAs($this->payment->customer)
            ->get("/dashboard/receipts/{$this->payment->id}?amount=1&total=1&customer=Palsu")
            ->assertOk()
            ->assertSee('Rp1.250.000')
            ->assertDontSee('Palsu');
    }

    public function test_customer_can_download_pdf(): void
    {
        $response = $this->actingAs($this->payment->customer)->get("/dashboard/receipts/{$this->payment->id}/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString("struk-{$this->payment->transaction_code}.pdf", $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_admin_can_view_receipt(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get("/dashboard/receipts/{$this->payment->id}")->assertOk();
        $this->actingAs($admin)->get("/dashboard/receipts/{$this->payment->id}/pdf")->assertOk();
    }

    public function test_freelancer_and_other_customers_cannot_access_receipt(): void
    {
        $order = $this->payment->order;

        foreach ([$order->freelancer, User::factory()->customer()->create()] as $user) {
            $this->actingAs($user)->get("/dashboard/receipts/{$this->payment->id}")->assertForbidden();
            $this->actingAs($user)->get("/dashboard/receipts/{$this->payment->id}/pdf")->assertForbidden();
        }

        $this->get('/logout');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get("/dashboard/receipts/{$this->payment->id}")->assertRedirect(route('login'));
    }

    public function test_non_successful_payment_has_no_receipt(): void
    {
        $order = Order::factory()->create();
        $pending = app(PaymentProcessor::class)->start($order, PaymentMethod::Qris, $order->customer);

        $this->actingAs($order->customer)->get("/dashboard/receipts/{$pending->id}")->assertNotFound();
        $this->actingAs($order->customer)->get("/dashboard/receipts/{$pending->id}/pdf")->assertNotFound();
    }

    public function test_payment_history_lists_only_own_payments_with_receipt_link(): void
    {
        $customer = $this->payment->customer;
        $other = Order::factory()->create(['title' => 'Pesanan Customer Lain']);
        app(PaymentProcessor::class)->start($other, PaymentMethod::Qris, $other->customer);

        $this->actingAs($customer)
            ->get('/dashboard/payments')
            ->assertOk()
            ->assertSee($this->payment->transaction_code)
            ->assertSee(route('receipts.show', $this->payment))
            ->assertDontSee('Pesanan Customer Lain');
    }

    public function test_successful_payment_page_links_to_receipt(): void
    {
        $this->actingAs($this->payment->customer)
            ->get("/payments/{$this->payment->id}")
            ->assertOk()
            ->assertSee(route('receipts.show', $this->payment))
            ->assertSee(route('receipts.pdf', $this->payment));
    }
}
