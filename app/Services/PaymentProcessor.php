<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Seluruh perubahan status pembayaran diproses di sini, di server, dalam transaksi
 * dengan baris terkunci. Status order hanya berubah menjadi dibayar lewat applyResult(),
 * tidak pernah karena customer membuka URL tertentu.
 */
class PaymentProcessor
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly Wallet $wallet,
    ) {}

    /**
     * Membuat tagihan baru untuk order. Tagihan menunggu sebelumnya dibatalkan
     * (misalnya customer mengganti metode pembayaran).
     *
     * @throws ValidationException
     */
    public function start(Order $order, PaymentMethod $method, User $customer): Payment
    {
        return DB::transaction(function () use ($order, $method, $customer) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $this->ensurePayable($order, $customer);

            Payment::where('order_id', $order->id)
                ->where('status', PaymentStatus::Pending)
                ->lockForUpdate()
                ->get()
                ->each(function (Payment $previous) use ($customer) {
                    $previous->status = PaymentStatus::Cancelled;
                    $previous->save();
                    $previous->logEvent('cancelled_replaced', PaymentStatus::Pending, PaymentStatus::Cancelled, $customer);
                });

            $payment = new Payment;
            $payment->transaction_code = Payment::generateTransactionCode();
            $payment->order_id = $order->id;
            $payment->customer_id = $order->customer_id;
            $payment->method = $method;
            $payment->provider = $this->gateway->name();
            $payment->currency = $order->currency;
            $payment->amount = $order->total_amount;
            $payment->commission_amount = $order->commission_amount;
            $payment->freelancer_amount = $order->freelancer_amount;
            $payment->status = PaymentStatus::Pending;

            $charge = $this->gateway->createCharge($payment);
            $payment->provider_reference = $charge['reference'];
            $payment->expires_at = $charge['expires_at'];
            $payment->save();

            $payment->logEvent('created', null, PaymentStatus::Pending, $customer, ['method' => $method->value, 'amount' => $payment->amount]);

            return $payment;
        });
    }

    /**
     * Menerapkan hasil pembayaran. Pada integrasi sungguhan dipanggil oleh handler
     * notifikasi provider yang sudah diverifikasi; pada mode demo dipanggil oleh tombol
     * simulasi customer. Idempoten: hasil yang sama untuk transaksi yang sudah final
     * diabaikan, dan transaksi berhasil tidak pernah diproses dua kali.
     *
     * @throws ValidationException
     */
    public function applyResult(Payment $payment, PaymentStatus $result, ?User $actor = null): Payment
    {
        if (! in_array($result, [PaymentStatus::Success, PaymentStatus::Pending, PaymentStatus::Failed], true)) {
            throw ValidationException::withMessages(['payment' => __('Hasil pembayaran tidak dikenal.')]);
        }

        $expired = false;

        $payment = DB::transaction(function () use ($payment, $result, $actor, &$expired) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if ($payment->status->isFinal()) {
                if ($payment->status === $result) {
                    $payment->logEvent('duplicate_ignored', $payment->status, $payment->status, $actor, ['result' => $result->value]);

                    return $payment;
                }

                throw ValidationException::withMessages([
                    'payment' => __('Transaksi :code sudah berstatus :status dan tidak dapat diproses lagi.', [
                        'code' => $payment->transaction_code,
                        'status' => $payment->status->getLabel(),
                    ]),
                ]);
            }

            if ($payment->isExpired()) {
                // Status kedaluwarsa disimpan dulu (commit), baru kemudian error dilempar.
                $payment->status = PaymentStatus::Expired;
                $payment->save();
                $payment->logEvent('expired', PaymentStatus::Pending, PaymentStatus::Expired, $actor);
                $expired = true;

                return $payment;
            }

            match ($result) {
                PaymentStatus::Pending => $payment->logEvent('simulated_pending', PaymentStatus::Pending, PaymentStatus::Pending, $actor),
                PaymentStatus::Failed => $this->markFailed($payment, $actor),
                PaymentStatus::Success => $this->markSuccess($payment, $order, $actor),
            };

            return $payment;
        });

        if ($expired) {
            throw ValidationException::withMessages(['payment' => __('Tagihan sudah kedaluwarsa. Buat pembayaran baru.')]);
        }

        return $payment;
    }

    private function markFailed(Payment $payment, ?User $actor): void
    {
        $payment->status = PaymentStatus::Failed;
        $payment->failed_at = now();
        $payment->save();

        $payment->logEvent('simulated_failed', PaymentStatus::Pending, PaymentStatus::Failed, $actor);
    }

    /**
     * @throws ValidationException
     */
    private function markSuccess(Payment $payment, Order $order, ?User $actor): void
    {
        if ($order->status !== OrderStatus::PendingPayment) {
            throw ValidationException::withMessages(['payment' => __('Pesanan ini tidak sedang menunggu pembayaran.')]);
        }

        // Integritas nominal: tagihan harus sama persis dengan total pesanan.
        if ($payment->amount !== $order->total_amount) {
            throw ValidationException::withMessages(['payment' => __('Nominal tagihan tidak sesuai dengan total pesanan.')]);
        }

        $payment->status = PaymentStatus::Success;
        $payment->paid_at = now();
        $payment->save();

        $payment->logEvent('simulated_success', PaymentStatus::Pending, PaymentStatus::Success, $actor, ['amount' => $payment->amount]);

        $order->transitionTo(OrderStatus::Paid, $actor, __('Pembayaran :code berhasil (simulasi).', ['code' => $payment->transaction_code]));

        // Pembayaran sudah terverifikasi (status berhasil + nominal cocok): dana masuk escrow.
        $this->wallet->holdForOrder($order);
        $order->transitionTo(OrderStatus::InProgress, null, __('Freelancer dapat mulai mengerjakan pesanan.'));

        ActivityNotification::send(
            $order->freelancer,
            __('Pesanan baru dibayar'),
            __(':title (:number) sudah dibayar customer. Silakan mulai mengerjakan.', ['title' => $order->title, 'number' => $order->order_number]),
            'orders.show',
            $order,
        );
    }

    /**
     * @throws ValidationException
     */
    private function ensurePayable(Order $order, User $customer): void
    {
        if ($order->customer_id !== $customer->id) {
            throw ValidationException::withMessages(['payment' => __('Hanya customer pemilik pesanan yang dapat membayar.')]);
        }

        if ($order->status !== OrderStatus::PendingPayment) {
            throw ValidationException::withMessages(['payment' => __('Pesanan ini tidak sedang menunggu pembayaran.')]);
        }
    }
}
