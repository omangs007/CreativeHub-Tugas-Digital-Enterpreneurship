<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Penarikan saldo freelancer. Komisi platform dihitung SEKALI saat pengajuan dan
 * disimpan (snapshot); pendapatan platform diakui saat status berhasil ditransfer.
 */
class Withdrawal extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount' => 'integer',
            'commission_rate_bps' => 'integer',
            'commission_amount' => 'integer',
            'net_amount' => 'integer',
            'processing_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(FreelancerWallet::class, 'freelancer_wallet_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function maskedAccountNumber(): string
    {
        $number = $this->account_number;

        return strlen($number) <= 4 ? $number : str_repeat('•', strlen($number) - 4).substr($number, -4);
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'WD-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
