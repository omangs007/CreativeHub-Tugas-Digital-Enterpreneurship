<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Filament\Resources\Withdrawals\Pages\ListWithdrawals;
use App\Models\FreelancerWallet;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\Wallet;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Escrow -> saldo tersedia -> penarikan dengan komisi platform 10%.
 */
class WalletTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        config(['creativehub.commission_rate_bps' => 1000]);
        $this->order = Order::factory()->total(1_000_000)->create();
        $this->order->recordHistory(null, OrderStatus::PendingPayment, $this->order->customer);
    }

    private function pay(): Payment
    {
        $customer = $this->order->customer;
        $this->actingAs($customer)->post("/checkout/{$this->order->id}/payments", ['method' => 'qris'])->assertSessionHasNoErrors();
        $payment = Payment::latest('id')->firstOrFail();
        $this->actingAs($customer)->post("/payments/{$payment->id}/simulate", ['result' => 'success'])->assertSessionHasNoErrors();

        return $payment;
    }

    private function deliver(): void
    {
        $this->actingAs($this->order->freelancer)
            ->post("/dashboard/orders/{$this->order->id}/deliveries", ['message' => 'Hasil pekerjaan sudah siap ditinjau.', 'link_url' => 'https://example.com/hasil'])
            ->assertSessionHasNoErrors();
    }

    private function approve(): void
    {
        $this->actingAs($this->order->customer)->post("/dashboard/orders/{$this->order->id}/complete")->assertSessionHasNoErrors();
    }

    private function wallet(): FreelancerWallet
    {
        return FreelancerWallet::where('user_id', $this->order->freelancer_id)->firstOrFail();
    }

    private function completedOrder(): void
    {
        $this->pay();
        $this->deliver();
        $this->approve();
    }

    /**
     * @return array<string, mixed>
     */
    private function bank(int $amount): array
    {
        return ['amount' => $amount, 'bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Freelancer Demo'];
    }

    private function withdraw(int $amount): Withdrawal
    {
        $freelancer = $this->order->freelancer;
        $this->actingAs($freelancer)->post('/dashboard/wallet/withdrawals/preview', $this->bank($amount))->assertOk();
        $token = session('withdrawal_confirm');
        $this->actingAs($freelancer)->post('/dashboard/wallet/withdrawals', $this->bank($amount) + ['confirm_token' => $token])
            ->assertRedirect(route('freelancer.wallet'))
            ->assertSessionHasNoErrors();

        return Withdrawal::latest('id')->firstOrFail();
    }

    public function test_unpaid_order_has_no_escrow(): void
    {
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->assertNull($this->order->escrowState());
    }

    public function test_verified_payment_moves_full_amount_into_escrow(): void
    {
        $this->pay();

        $wallet = $this->wallet();
        $this->assertSame(1_000_000, $wallet->held_balance);
        $this->assertSame(0, $wallet->available_balance);
        $this->assertSame('held', $this->order->fresh()->escrowState());

        $this->actingAs($this->order->freelancer)->get('/dashboard/wallet')
            ->assertOk()
            ->assertSee('Saldo tertahan (escrow)')
            ->assertSee('Rp1.000.000')
            ->assertSee($this->order->title)
            ->assertSee('belum mencapai minimal penarikan');
    }

    public function test_failed_or_duplicate_payment_results_do_not_double_escrow(): void
    {
        $payment = $this->pay();

        // Notifikasi sukses yang sama dikirim ulang: diabaikan.
        $this->actingAs($this->order->customer)->post("/payments/{$payment->id}/simulate", ['result' => 'success']);
        app(Wallet::class)->holdForOrder($this->order->fresh());

        $this->assertSame(1, WalletTransaction::where('type', WalletTransactionType::EscrowHold)->count());
        $this->assertSame(1_000_000, $this->wallet()->held_balance);
    }

    public function test_database_rejects_second_escrow_record_for_same_order(): void
    {
        $this->pay();
        $hold = WalletTransaction::sole();

        $this->expectException(QueryException::class);
        $copy = $hold->replicate();
        $copy->save();
    }

    public function test_escrow_requires_verified_payment(): void
    {
        $order = Order::factory()->total(400_000)->status(OrderStatus::InProgress)->create();

        $this->expectException(ValidationException::class);
        app(Wallet::class)->holdForOrder($order);
    }

    public function test_revision_keeps_funds_held_and_approval_releases_them(): void
    {
        $this->pay();
        $this->deliver();

        $this->actingAs($this->order->customer)
            ->post("/dashboard/orders/{$this->order->id}/revisions", ['reason' => 'Tolong ganti warna latar menjadi biru.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1_000_000, $this->wallet()->held_balance);
        $this->assertSame(0, $this->wallet()->available_balance);

        $this->deliver();
        $this->approve();

        $wallet = $this->wallet();
        $this->assertSame(0, $wallet->held_balance);
        $this->assertSame(1_000_000, $wallet->available_balance);
        $this->assertSame('released', $this->order->fresh()->escrowState());
        $this->assertStringContainsString('masuk saldo tersedia', $this->order->freelancer->notifications->firstWhere('data.title', 'Pesanan selesai')->data['body']);
    }

    public function test_held_balance_cannot_be_withdrawn(): void
    {
        $this->pay();

        $this->actingAs($this->order->freelancer)
            ->post('/dashboard/wallet/withdrawals/preview', $this->bank(500_000))
            ->assertSessionHasErrors('amount');

        $this->expectException(ValidationException::class);
        app(Wallet::class)->requestWithdrawal($this->order->freelancer, 500_000, $this->bank(0));
    }

    public function test_withdrawal_shows_breakdown_and_deducts_ten_percent_once(): void
    {
        $this->completedOrder();
        $freelancer = $this->order->freelancer;

        $this->actingAs($freelancer)->post('/dashboard/wallet/withdrawals/preview', $this->bank(1_000_000))
            ->assertOk()
            ->assertSee('Konfirmasi Penarikan')
            ->assertSee('Rp1.000.000')
            ->assertSee('- Rp100.000', false)
            ->assertSee('Rp900.000');

        // Pratinjau belum mengubah saldo.
        $this->assertSame(1_000_000, $this->wallet()->available_balance);
        $this->assertDatabaseCount('withdrawals', 0);

        $token = session('withdrawal_confirm');
        $this->actingAs($freelancer)->post('/dashboard/wallet/withdrawals', $this->bank(1_000_000) + ['confirm_token' => $token]);

        $withdrawal = Withdrawal::sole();
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->status);
        $this->assertSame(1_000_000, $withdrawal->amount);
        $this->assertSame(1000, $withdrawal->commission_rate_bps);
        $this->assertSame(100_000, $withdrawal->commission_amount);
        $this->assertSame(900_000, $withdrawal->net_amount);
        $this->assertSame(0, $this->wallet()->available_balance);

        // Kirim ulang form konfirmasi (token sudah terpakai): tidak ada penarikan kedua.
        $this->actingAs($freelancer)->post('/dashboard/wallet/withdrawals', $this->bank(1_000_000) + ['confirm_token' => $token])
            ->assertRedirect(route('freelancer.wallet'));
        $this->assertDatabaseCount('withdrawals', 1);
    }

    public function test_withdrawal_cannot_exceed_available_balance(): void
    {
        $this->completedOrder();
        $this->withdraw(600_000);

        $this->assertSame(400_000, $this->wallet()->available_balance);

        $this->expectException(ValidationException::class);
        app(Wallet::class)->requestWithdrawal($this->order->freelancer, 400_001, $this->bank(0));
    }

    public function test_admin_processes_and_confirms_transfer(): void
    {
        $this->completedOrder();
        $withdrawal = $this->withdraw(1_000_000);
        $admin = User::factory()->admin()->create();
        Filament::setCurrentPanel('admin');

        $this->actingAs($admin)->get('/admin/withdrawals')->assertOk()->assertSee($withdrawal->reference);

        // Tidak bisa langsung dikonfirmasi berhasil sebelum diproses.
        $this->expectExceptionOnComplete($withdrawal, $admin);

        Livewire::actingAs($admin)->test(ListWithdrawals::class)
            ->callTableAction('process', $withdrawal)
            ->assertHasNoTableActionErrors();
        $this->assertSame(WithdrawalStatus::Processing, $withdrawal->fresh()->status);

        Livewire::actingAs($admin)->test(ListWithdrawals::class)
            ->callTableAction('complete', $withdrawal, ['transfer_reference' => 'BCA-TRF-0001'])
            ->assertHasNoTableActionErrors();

        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Completed, $withdrawal->status);
        $this->assertSame('BCA-TRF-0001', $withdrawal->transfer_reference);
        $this->assertNotNull($withdrawal->completed_at);
        $this->assertSame(0, $this->wallet()->available_balance);

        $this->actingAs($this->order->freelancer)->get('/dashboard/wallet')
            ->assertSee('Berhasil Ditransfer')
            ->assertSee('BCA-TRF-0001')
            ->assertSee('Rp900.000');
    }

    private function expectExceptionOnComplete(Withdrawal $withdrawal, User $admin): void
    {
        try {
            app(Wallet::class)->markCompleted($withdrawal, $admin, 'X');
            $this->fail('Penarikan yang belum diproses tidak boleh dikonfirmasi.');
        } catch (ValidationException) {
            $this->assertSame(WithdrawalStatus::Pending, $withdrawal->fresh()->status);
        }
    }

    public function test_rejected_withdrawal_refunds_full_amount_once(): void
    {
        $this->completedOrder();
        $withdrawal = $this->withdraw(1_000_000);
        $admin = User::factory()->admin()->create();
        Filament::setCurrentPanel('admin');

        Livewire::actingAs($admin)->test(ListWithdrawals::class)
            ->callTableAction('reject', $withdrawal, ['reason' => 'Nomor rekening tidak ditemukan.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(WithdrawalStatus::Rejected, $withdrawal->fresh()->status);
        $this->assertSame(1_000_000, $this->wallet()->available_balance);

        $this->expectException(ValidationException::class);
        app(Wallet::class)->reject($withdrawal, $admin, 'lagi');
    }

    public function test_wallet_and_withdrawal_admin_are_role_restricted(): void
    {
        $this->actingAs($this->order->customer)->get('/dashboard/wallet')->assertForbidden();
        $this->actingAs($this->order->customer)->post('/dashboard/wallet/withdrawals/preview', $this->bank(10_000))->assertForbidden();
        $this->actingAs($this->order->freelancer)->get('/admin/withdrawals')->assertForbidden();
        $this->post('/logout');
        $this->get('/dashboard/wallet')->assertRedirect(route('login'));
    }

    public function test_freelancer_order_page_shows_escrow_status_without_per_order_commission(): void
    {
        $this->pay();

        $this->actingAs($this->order->freelancer)->get("/dashboard/orders/{$this->order->id}")
            ->assertOk()
            ->assertSee('Tertahan (escrow)')
            ->assertSee('dipotong saat penarikan saldo')
            ->assertDontSee('Bagian Anda');
    }

    public function test_backfill_records_existing_paid_orders_idempotently(): void
    {
        $this->completedOrder();
        $second = Order::factory()->total(200_000)->create(['freelancer_id' => $this->order->freelancer_id]);
        $second->recordHistory(null, OrderStatus::PendingPayment, $second->customer);
        $this->order = $second;
        $this->pay();

        // Simulasikan data sebelum fitur ada: kosongkan ledger dan saldo.
        WalletTransaction::query()->delete();
        FreelancerWallet::query()->update(['held_balance' => 0, 'available_balance' => 0]);

        $wallet = app(Wallet::class);
        $this->assertSame(2, $wallet->backfillFromOrders());
        $this->assertSame(2, $wallet->backfillFromOrders());

        $balance = $this->wallet();
        $this->assertSame(200_000, $balance->held_balance);
        $this->assertSame(1_000_000, $balance->available_balance);
        $this->assertSame(3, WalletTransaction::count());
    }
}
