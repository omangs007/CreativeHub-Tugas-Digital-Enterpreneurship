<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WithdrawalStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Diproses',
            self::Processing => 'Sedang Ditransfer',
            self::Completed => 'Berhasil Ditransfer',
            self::Rejected => 'Gagal / Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Rejected => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
