<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Qris = 'qris';
    case VirtualAccount = 'virtual_account';
    case Ewallet = 'ewallet';

    public function getLabel(): string
    {
        return match ($this) {
            self::Qris => 'QRIS (Simulasi)',
            self::VirtualAccount => 'Transfer Virtual Account (Simulasi)',
            self::Ewallet => 'E-Wallet (Simulasi)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Qris => 'Pindai kode QR dummy. Tidak ada aplikasi bank yang terhubung.',
            self::VirtualAccount => 'Nomor virtual account dummy, tidak terhubung ke bank mana pun.',
            self::Ewallet => 'Pembayaran e-wallet dummy, tidak terhubung ke penyedia e-wallet.',
        };
    }
}
