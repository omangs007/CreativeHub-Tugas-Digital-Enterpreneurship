<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProposalStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Accepted => 'Diterima',
            self::Rejected => 'Ditolak',
            self::Withdrawn => 'Ditarik',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Withdrawn => 'gray',
        };
    }
}
