<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saldo freelancer: tertahan (escrow, pesanan belum disetujui) dan tersedia (boleh ditarik).
 * Hanya diubah lewat App\Services\Wallet di dalam transaksi dengan baris terkunci.
 */
class FreelancerWallet extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'held_balance' => 'integer',
            'available_balance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
}
