<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingReview => 'Menunggu Review',
            self::Published => 'Dipublikasikan',
            self::Rejected => 'Ditolak',
        };
    }
}
