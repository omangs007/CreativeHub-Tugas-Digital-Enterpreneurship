<?php

namespace Tests\Feature\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentProcessor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DummyPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = Order::factory()->total(500_000)->create();
        $this->order->recordHistory(null, OrderStatus::PendingPayment, $this->order->customer);
    }

    private function startPayment(string $method = 'qris'): Payment
    {
        $this->actingAs($this->order->customer)
            ->post("/checkout/{$this->order->id}/payments", ['method' => $method])
            ->assertSessionHasNoErrors();

        return Payment::latest('id')->firstOrFail();
    }

    public function test_customer_sees_checkout_with_demo_banner_and_methods(): void
    {
        $this->actingAs($this->order->customer)
            ->get("/checkout/{$this->order->id}")
            ->assertOk()
            ->assertSee('Mode Demo — pembayaran tidak menggunakan uang sungguhan')
            ->assertSee('QRIS (Simulasi)')
            ->assertSee('Transfer Virtual Account (Simulasi)')
            ->assertSee('E-Wallet (Simulasi)')
            ->assertSee('Rp500.000');

        $this->actingAs($this->order->customer)->get("/dashboard/orders/{$this->order->id}")->assertSee('Bayar Sekarang');
    }

    public function test_only_order_customer_can_start_payment(): void
    {
        $this->actingAs($this->order->freelancer)->get("/checkout/{$this->order->id}")->assertForbidden();
        $this->actingAs($this->order->freelancer)->post("/checkout/{$this->order->id}/payments", ['method' => 'qris'])->assertForbidden();
        $this->actingAs(User::factory()->customer()->create())->post("/checkout/{$this->order->id}/payments", ['method' => 'qris'])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post("/checkout/{$this->order->id}/payments", ['method' => 'qris'])->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_starting_payment_uses_server_amount_and_creates_reference(): void
    {
        foreach (PaymentMethod::cases() as $method) {
            $response = $this->actingAs($this->order->customer)->post("/checkout/{$this->order->id}/payments", [
                'method' => $method->value,
                'amount' => 1,
            ]);

            $payment = Payment::latest('id')->firstOrFail();
            $response->assertRedirect("/payments/{$payment->id}");

            $this->assertSame($method, $payment->method);
            $this->assertSame(PaymentStatus::Pending, $payment->status);
            $this->assertSame(500_000, $payment->amount);
            $this->assertSame(50_000, $payment->commission_amount);
            $this->assertSame(450_000, $payment->freelancer_amount);
            $this->assertSame('dummy', $payment->provider);
            $this->assertNotEmpty($payment->provider_reference);
            $this->assertMatchesRegularExpression('/^TRX-\d{8}-[A-Z0-9]{8}$/', $payment->transaction_code);
            $this->assertTrue($payment->expires_at->isFuture());
        }

        // Ganti metode membatalkan tagihan menunggu sebelumnya; hanya satu yang menunggu.
        $this->assertSame(1, Payment::where('status', PaymentStatus::Pending)->count());
        $this->assertSame(2, Payment::where('status', PaymentStatus::Cancelled)->count());
    }

    public function test_invalid_method_is_rejected(): void
    {
        $this->actingAs($this->order->customer)
            ->post("/checkout/{$this->order->id}/payments", ['method' => 'credit_card'])
            ->assertSessionHasErrors('method');
    }

    public function test_successful_simulation_marks_order_paid_and_in_progress(): void
    {
        $payment = $this->startPayment('virtual_account');

        $this->actingAs($this->order->customer)
            ->post("/payments/{$payment->id}/simulate", ['result' => 'success'])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/payments/{$payment->id}");

        $payment->refresh();
        $order = $this->order->fresh();

        $this->assertSame(PaymentStatus::Success, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(OrderStatus::InProgress, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(
            [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::InProgress],
            $order->statusHistories()->get()->pluck('to_status')->all(),
        );
        $this->assertSame(['created', 'simulated_success'], $payment->events()->pluck('event')->all());
    }

    public function test_success_is_processed_only_once(): void
    {
        $payment = $this->startPayment();

        $this->actingAs($this->order->customer)->post("/payments/{$payment->id}/simulate", ['result' => 'success']);
        $this->actingAs($this->order->customer)->post("/payments/{$payment->id}/simulate", ['result' => 'success'])->assertSessionHasNoErrors();
        $this->actingAs($this->order->customer)->post("/payments/{$payment->id}/simulate", ['result' => 'failed'])->assertSessionHasErrors('payment');

        $this->assertSame(PaymentStatus::Success, $payment->fresh()->status);
        $this->assertSame(3, $this->order->statusHistories()->count());
        $this->assertSame(1, $payment->events()->where('event', 'duplicate_ignored')->count());

        // Setelah dibayar, pesanan tidak bisa ditagih lagi.
        $this->actingAs($this->order->customer)->post("/checkout/{$this->order->id}/payments", ['method' => 'qris'])->assertForbidden();
    }

    public function test_failed_simulation_keeps_order_pending_and_allows_retry(): void
    {
        $payment = $this->startPayment();

        $this->actingAs($this->order->customer)->post("/payments/{$payment->id}/simulate", ['result' => 'failed'])->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->failed_at);
        $this->assertSame(OrderStatus::PendingPayment, $this->order->fresh()->status);

        $retry = $this->startPayment('ewallet');
        $this->actingAs($this->order->customer)->post("/payments/{$retry->id}/simulate", ['result' => 'success'])->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::InProgress, $this->order->fresh()->status);
        $this->assertSame(1, Payment::where('status', PaymentStatus::Success)->count());
    }

    public function test_pending_simulation_keeps_everything_pending(): void
    {
        $payment = $this->startPayment();

        $this->actingAs($this->order->customer)->post("/payments/{$payment->id}/simulate", ['result' => 'pending'])->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $this->order->fresh()->status);
        $this->assertSame(1, $payment->events()->where('event', 'simulated_pending')->count());
    }

    public function test_opening_success_url_does_not_change_status(): void
    {
        $payment = $this->startPayment();

        $this->actingAs($this->order->customer)->get("/payments/{$payment->id}?status=success&result=success")->assertOk();
        $this->actingAs($this->order->customer)->get("/payments/{$payment->id}/simulate?result=success")->assertMethodNotAllowed();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $this->order->fresh()->status);
    }

    public function test_only_payment_owner_can_view_or_simulate(): void
    {
        $payment = $this->startPayment();

        $this->actingAs($this->order->freelancer)->get("/payments/{$payment->id}")->assertForbidden();
        $this->actingAs($this->order->freelancer)->post("/payments/{$payment->id}/simulate", ['result' => 'success'])->assertForbidden();
        $this->actingAs(User::factory()->customer()->create())->post("/payments/{$payment->id}/simulate", ['result' => 'success'])->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get("/payments/{$payment->id}")->assertOk()->assertDontSee('Simulasikan Berhasil');
        $this->actingAs($admin)->post("/payments/{$payment->id}/simulate", ['result' => 'success'])->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_invalid_simulation_result_is_rejected(): void
    {
        $payment = $this->startPayment();

        $this->actingAs($this->order->customer)
            ->post("/payments/{$payment->id}/simulate", ['result' => 'cancelled'])
            ->assertSessionHasErrors('result');
    }

    public function test_expired_payment_cannot_be_completed(): void
    {
        $payment = $this->startPayment();
        $payment->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->actingAs($this->order->customer)
            ->post("/payments/{$payment->id}/simulate", ['result' => 'success'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(PaymentStatus::Expired, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $this->order->fresh()->status);
    }

    public function test_tampered_amount_is_refused(): void
    {
        $payment = $this->startPayment();
        // Simulasi data tagihan yang dimanipulasi langsung di database.
        $payment->forceFill(['amount' => 1000, 'commission_amount' => 100, 'freelancer_amount' => 900])->save();

        $this->expectException(ValidationException::class);

        app(PaymentProcessor::class)->applyResult($payment, PaymentStatus::Success, $this->order->customer);
    }

    public function test_database_allows_only_one_successful_payment_per_order(): void
    {
        Payment::factory()->for($this->order)->create(['status' => PaymentStatus::Success, 'paid_at' => now()]);

        $this->expectException(UniqueConstraintViolationException::class);

        Payment::factory()->for($this->order)->create(['status' => PaymentStatus::Success, 'paid_at' => now()]);
    }
}
