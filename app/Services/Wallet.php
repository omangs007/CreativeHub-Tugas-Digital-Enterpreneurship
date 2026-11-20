<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Models\FreelancerWallet;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Notifications\ActivityNotification;
use App\Support\AuditLogger;
use App\Support\CommissionCalculator;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Escrow, saldo freelancer, dan penarikan.
 *
 * Alur dana: pembayaran terverifikasi -> saldo tertahan (escrow) -> customer menyetujui
 * -> saldo tersedia -> penarikan (komisi platform dipotong sekali di sini) -> transfer.
 *
 * Setiap perubahan saldo terjadi di dalam transaksi database dengan baris dompet
 * terkunci (SELECT ... FOR UPDATE) dan dicatat di wallet_transactions. Unique index
 * pada (order_id, type) / (withdrawal_id, type) serta CHECK saldo >= 0 menjadi
 * pengaman terakhir terhadap pencatatan ganda dan saldo minus.
 *
 * Catatan: di CreativeHub dana fisik tidak benar-benar ditahan (gateway simulasi).
 * Pada produksi, dana ditampung di rekening penampungan/escrow milik penyedia
 * pembayaran berizin dan transfer ke freelancer memakai layanan disbursement-nya;
 * kelas ini tetap menjadi buku besar (ledger) yang menentukan hak masing-masing pihak.
 */
class Wallet
{
    /**
     * Status pesanan yang dananya sudah terverifikasi dibayar.
     */
    public const FUNDED_STATUSES = [
        OrderStatus::Paid, OrderStatus::InProgress, OrderStatus::Delivered,
        OrderStatus::RevisionRequested, OrderStatus::Completed,
    ];

    /**
     * Catat dana pesanan sebagai escrow (saldo tertahan freelancer).
     * Wajib dipanggil di dalam transaksi setelah pembayaran berstatus berhasil.
     * Idempoten: pesanan yang sudah tercatat tidak ditambahkan lagi.
     *
     * @throws ValidationException
     */
    public function holdForOrder(Order $order): WalletTransaction
    {
        return DB::transaction(function () use ($order) {
            // Kunci dompet lebih dulu agar pengecekan "sudah tercatat" tidak balapan.
            $wallet = $this->lockWallet($order->freelancer_id);
            $existing = $this->orderTransaction($order, WalletTransactionType::EscrowHold);

            if ($existing) {
                return $existing;
            }

            $amount = $this->verifiedPaymentAmount($order);

            if ($amount === null) {
                throw ValidationException::withMessages([
                    'payment' => __('Dana belum dapat dicatat sebagai escrow karena pembayaran pesanan belum terverifikasi.'),
                ]);
            }

            $wallet->held_balance += $amount;
            $wallet->save();

            return $this->record($wallet, WalletTransactionType::EscrowHold, $amount,
                __('Dana pesanan :number masuk escrow (tertahan).', ['number' => $order->order_number]), order: $order);
        });
    }

    /**
     * Pindahkan escrow pesanan ke saldo tersedia setelah customer menyetujui hasil.
     * Pesanan tanpa pembayaran terverifikasi tidak menghasilkan saldo (mengembalikan null).
     */
    public function releaseForOrder(Order $order): ?WalletTransaction
    {
        return DB::transaction(function () use ($order) {
            $this->lockWallet($order->freelancer_id);
            $existing = $this->orderTransaction($order, WalletTransactionType::EscrowRelease);

            if ($existing) {
                return $existing;
            }

            if ($order->status !== OrderStatus::Completed) {
                throw ValidationException::withMessages(['order' => __('Escrow hanya dicairkan setelah customer menyetujui hasil pekerjaan.')]);
            }

            if (! $this->orderTransaction($order, WalletTransactionType::EscrowHold)) {
                // Pesanan lama yang dibayar sebelum fitur escrow: catat escrow-nya dulu.
                if ($this->verifiedPaymentAmount($order) === null) {
                    return null;
                }

                $this->holdForOrder($order);
            }

            $hold = $this->orderTransaction($order, WalletTransactionType::EscrowHold);
            $wallet = $this->lockWallet($order->freelancer_id);

            $wallet->held_balance -= $hold->amount;
            $wallet->available_balance += $hold->amount;
            $wallet->save();

            return $this->record($wallet, WalletTransactionType::EscrowRelease, $hold->amount,
                __('Pesanan :number disetujui customer, dana masuk saldo tersedia.', ['number' => $order->order_number]), order: $order);
        });
    }

    /**
     * Rincian penarikan yang ditampilkan sebelum freelancer mengonfirmasi.
     *
     * @return array{amount: int, rate_bps: int, commission: int, net: int}
     */
    public function quote(int $amount): array
    {
        $split = CommissionCalculator::split($amount);

        return [
            'amount' => $amount,
            'rate_bps' => $split['rate_bps'],
            'commission' => $split['commission'],
            'net' => $split['freelancer'],
        ];
    }

    public static function minimumWithdrawal(): int
    {
        return (int) config('creativehub.withdrawal_min_amount');
    }

    /**
     * Ajukan penarikan. Saldo tersedia langsung didebit (dicadangkan) sehingga tidak
     * dapat ditarik dua kali; jika transfer gagal, nominal dikembalikan utuh.
     *
     * @param  array{bank_name: string, account_number: string, account_holder: string}  $bank
     *
     * @throws ValidationException
     */
    public function requestWithdrawal(User $freelancer, int $amount, array $bank): Withdrawal
    {
        if ($amount < self::minimumWithdrawal()) {
            throw ValidationException::withMessages(['amount' => __('Minimal penarikan :min.', ['min' => Rupiah::format(self::minimumWithdrawal())])]);
        }

        return DB::transaction(function () use ($freelancer, $amount, $bank) {
            $wallet = $this->lockWallet($freelancer->id);

            if ($amount > $wallet->available_balance) {
                throw ValidationException::withMessages(['amount' => __('Nominal melebihi saldo tersedia (:balance). Saldo tertahan belum dapat ditarik.', [
                    'balance' => Rupiah::format($wallet->available_balance),
                ])]);
            }

            $quote = $this->quote($amount);

            $withdrawal = new Withdrawal;
            $withdrawal->reference = Withdrawal::generateReference();
            $withdrawal->user_id = $freelancer->id;
            $withdrawal->freelancer_wallet_id = $wallet->id;
            $withdrawal->amount = $quote['amount'];
            $withdrawal->commission_rate_bps = $quote['rate_bps'];
            $withdrawal->commission_amount = $quote['commission'];
            $withdrawal->net_amount = $quote['net'];
            $withdrawal->bank_name = $bank['bank_name'];
            $withdrawal->account_number = $bank['account_number'];
            $withdrawal->account_holder = $bank['account_holder'];
            $withdrawal->status = WithdrawalStatus::Pending;
            $withdrawal->save();

            $wallet->available_balance -= $amount;
            $wallet->save();

            $this->record($wallet, WalletTransactionType::Withdrawal, $amount,
                __('Penarikan :ref diajukan (komisi :commission, diterima :net).', [
                    'ref' => $withdrawal->reference,
                    'commission' => Rupiah::format($quote['commission']),
                    'net' => Rupiah::format($quote['net']),
                ]), withdrawal: $withdrawal);

            AuditLogger::record('withdrawal.requested', $withdrawal, null, ['amount' => $amount, 'commission' => $quote['commission'], 'net' => $quote['net']], $freelancer);

            return $withdrawal;
        });
    }

    /**
     * @throws ValidationException
     */
    public function markProcessing(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin) {
            $withdrawal = $this->lockWithdrawal($withdrawal, [WithdrawalStatus::Pending]);

            $withdrawal->status = WithdrawalStatus::Processing;
            $withdrawal->processing_at = now();
            $withdrawal->processed_by = $admin->id;
            $withdrawal->save();

            AuditLogger::record('withdrawal.processing', $withdrawal, ['status' => WithdrawalStatus::Pending->value], ['status' => WithdrawalStatus::Processing->value], $admin);

            ActivityNotification::send($withdrawal->user, __('Penarikan sedang diproses'),
                __('Penarikan :ref sebesar :net sedang ditransfer ke rekening Anda.', ['ref' => $withdrawal->reference, 'net' => Rupiah::format($withdrawal->net_amount)]),
                'freelancer.wallet');

            return $withdrawal;
        });
    }

    /**
     * Konfirmasi transfer berhasil. Komisi menjadi pendapatan platform pada tahap ini.
     *
     * @throws ValidationException
     */
    public function markCompleted(Withdrawal $withdrawal, User $admin, string $transferReference): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $transferReference) {
            $withdrawal = $this->lockWithdrawal($withdrawal, [WithdrawalStatus::Processing]);

            $withdrawal->status = WithdrawalStatus::Completed;
            $withdrawal->transfer_reference = $transferReference;
            $withdrawal->completed_at = now();
            $withdrawal->processed_by = $admin->id;
            $withdrawal->save();

            AuditLogger::record('withdrawal.completed', $withdrawal, ['status' => WithdrawalStatus::Processing->value],
                ['status' => WithdrawalStatus::Completed->value, 'transfer_reference' => $transferReference], $admin);

            ActivityNotification::send($withdrawal->user, __('Penarikan berhasil'),
                __(':net dari penarikan :ref telah ditransfer ke :bank.', [
                    'net' => Rupiah::format($withdrawal->net_amount), 'ref' => $withdrawal->reference, 'bank' => $withdrawal->bank_name,
                ]), 'freelancer.wallet');

            return $withdrawal;
        });
    }

    /**
     * Tolak / gagalkan penarikan; nominal kotor dikembalikan utuh ke saldo tersedia.
     *
     * @throws ValidationException
     */
    public function reject(Withdrawal $withdrawal, User $admin, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $withdrawal = $this->lockWithdrawal($withdrawal, [WithdrawalStatus::Pending, WithdrawalStatus::Processing]);
            $previous = $withdrawal->status;

            $withdrawal->status = WithdrawalStatus::Rejected;
            $withdrawal->admin_note = $reason;
            $withdrawal->rejected_at = now();
            $withdrawal->processed_by = $admin->id;
            $withdrawal->save();

            $wallet = $this->lockWallet($withdrawal->user_id);
            $wallet->available_balance += $withdrawal->amount;
            $wallet->save();

            $this->record($wallet, WalletTransactionType::WithdrawalRefund, $withdrawal->amount,
                __('Penarikan :ref gagal/ditolak, saldo dikembalikan.', ['ref' => $withdrawal->reference]), withdrawal: $withdrawal);

            AuditLogger::record('withdrawal.rejected', $withdrawal, ['status' => $previous->value], ['status' => WithdrawalStatus::Rejected->value, 'reason' => $reason], $admin);

            ActivityNotification::send($withdrawal->user, __('Penarikan gagal'),
                __('Penarikan :ref tidak dapat diproses: :reason. Saldo :amount dikembalikan ke saldo tersedia.', [
                    'ref' => $withdrawal->reference, 'reason' => $reason, 'amount' => Rupiah::format($withdrawal->amount),
                ]), 'freelancer.wallet');

            return $withdrawal;
        });
    }

    /**
     * Ambil (tanpa membuat) dompet untuk tampilan.
     */
    public function walletFor(User $user): FreelancerWallet
    {
        return FreelancerWallet::firstWhere('user_id', $user->id)
            ?? (new FreelancerWallet)->forceFill(['user_id' => $user->id, 'held_balance' => 0, 'available_balance' => 0]);
    }

    /**
     * Catat escrow untuk pesanan yang sudah dibayar sebelum fitur ini ada. Aman dijalankan ulang.
     */
    public function backfillFromOrders(): int
    {
        $count = 0;

        Order::whereIn('status', self::FUNDED_STATUSES)->orderBy('id')->each(function (Order $order) use (&$count) {
            if ($this->verifiedPaymentAmount($order) === null) {
                return;
            }

            DB::transaction(function () use ($order) {
                $this->holdForOrder($order);

                if ($order->status === OrderStatus::Completed) {
                    $this->releaseForOrder($order);
                }
            });

            $count++;
        });

        return $count;
    }

    /**
     * Nominal pembayaran berhasil yang cocok dengan total pesanan, atau null bila belum terverifikasi.
     */
    private function verifiedPaymentAmount(Order $order): ?int
    {
        if (! in_array($order->status, self::FUNDED_STATUSES, true)) {
            return null;
        }

        $payment = Payment::where('order_id', $order->id)->where('status', PaymentStatus::Success)->first();

        if (! $payment || $payment->paid_at === null || $payment->amount !== $order->total_amount) {
            return null;
        }

        return $payment->amount;
    }

    private function orderTransaction(Order $order, WalletTransactionType $type): ?WalletTransaction
    {
        return WalletTransaction::where('order_id', $order->id)->where('type', $type)->first();
    }

    private function lockWallet(int $userId): FreelancerWallet
    {
        DB::table('freelancer_wallets')->insertOrIgnore([
            'user_id' => $userId,
            'held_balance' => 0,
            'available_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return FreelancerWallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  list<WithdrawalStatus>  $allowed
     *
     * @throws ValidationException
     */
    private function lockWithdrawal(Withdrawal $withdrawal, array $allowed): Withdrawal
    {
        $locked = Withdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

        if (! in_array($locked->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'withdrawal' => __('Penarikan :ref sudah berstatus ":status".', ['ref' => $locked->reference, 'status' => $locked->status->getLabel()]),
            ]);
        }

        return $locked;
    }

    private function record(FreelancerWallet $wallet, WalletTransactionType $type, int $amount, string $description, ?Order $order = null, ?Withdrawal $withdrawal = null): WalletTransaction
    {
        $transaction = new WalletTransaction;
        $transaction->freelancer_wallet_id = $wallet->id;
        $transaction->order_id = $order?->id;
        $transaction->withdrawal_id = $withdrawal?->id;
        $transaction->type = $type;
        $transaction->amount = $amount;
        $transaction->held_after = $wallet->held_balance;
        $transaction->available_after = $wallet->available_balance;
        $transaction->description = $description;
        $transaction->save();

        return $transaction;
    }
}
