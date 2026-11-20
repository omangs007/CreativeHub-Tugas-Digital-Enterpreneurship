<?php

namespace App\Models;

use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mutasi saldo freelancer (append-only). Unique index (order_id, type) dan
 * (withdrawal_id, type) mencegah pencatatan ganda.
 */
class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'amount' => 'integer',
            'held_after' => 'integer',
            'available_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(FreelancerWallet::class, 'freelancer_wallet_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    /**
     * Arah mutasi terhadap saldo tersedia: +1 masuk, -1 keluar, 0 hanya saldo tertahan.
     */
    public function availableDirection(): int
    {
        return match ($this->type) {
            WalletTransactionType::EscrowHold => 0,
            WalletTransactionType::EscrowRelease, WalletTransactionType::WithdrawalRefund => 1,
            WalletTransactionType::Withdrawal => -1,
        };
    }
}
