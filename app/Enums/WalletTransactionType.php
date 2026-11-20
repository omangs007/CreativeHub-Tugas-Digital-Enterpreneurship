<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Jenis mutasi saldo freelancer.
 *
 * - EscrowHold: pembayaran pesanan terverifikasi, dana masuk saldo tertahan.
 * - EscrowRelease: customer menyetujui hasil, dana pindah ke saldo tersedia.
 * - Withdrawal: freelancer menarik saldo tersedia (nominal kotor, sebelum komisi).
 * - WithdrawalRefund: penarikan gagal/ditolak, nominal kotor kembali ke saldo tersedia.
 */
enum WalletTransactionType: string implements HasColor, HasLabel
{
    case EscrowHold = 'escrow_hold';
    case EscrowRelease = 'escrow_release';
    case Withdrawal = 'withdrawal';
    case WithdrawalRefund = 'withdrawal_refund';

    public function getLabel(): string
    {
        return match ($this) {
            self::EscrowHold => 'Dana masuk escrow',
            self::EscrowRelease => 'Escrow dicairkan',
            self::Withdrawal => 'Penarikan saldo',
            self::WithdrawalRefund => 'Pengembalian penarikan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EscrowHold => 'warning',
            self::EscrowRelease => 'success',
            self::Withdrawal => 'gray',
            self::WithdrawalRefund => 'info',
        };
    }
}
