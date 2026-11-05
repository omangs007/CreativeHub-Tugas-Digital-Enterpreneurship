<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case RevisionRequested = 'revision_requested';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::Paid => 'Dibayar',
            self::InProgress => 'Dikerjakan',
            self::Delivered => 'Hasil Dikirim',
            self::RevisionRequested => 'Revisi Diminta',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Paid, self::InProgress => 'info',
            self::Delivered => 'primary',
            self::RevisionRequested => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Transisi status yang diizinkan. Dicek oleh Order::transitionTo().
     *
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Paid, self::Cancelled],
            self::Paid => [self::InProgress],
            self::InProgress => [self::Delivered],
            self::Delivered => [self::RevisionRequested, self::Completed],
            self::RevisionRequested => [self::Delivered],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
