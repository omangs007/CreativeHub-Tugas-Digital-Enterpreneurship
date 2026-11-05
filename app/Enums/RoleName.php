<?php

namespace App\Enums;

/**
 * Peran akun yang tersimpan di tabel `roles`.
 *
 * Guest tidak disimpan sebagai role: guest adalah pengunjung yang belum login.
 */
enum RoleName: string
{
    case Customer = 'customer';
    case Freelancer = 'freelancer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Freelancer => 'Freelancer',
            self::Admin => 'Admin',
        };
    }
}
