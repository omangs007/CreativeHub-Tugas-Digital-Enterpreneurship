<?php

namespace App\Payments;

use App\Enums\PaymentMethod;
use App\Models\Payment;

/**
 * Gateway SIMULASI. Tidak terhubung ke bank, QRIS, atau e-wallet mana pun,
 * dan tidak memindahkan uang sungguhan.
 */
class DummyPaymentGateway implements PaymentGateway
{
    public const EXPIRY_HOURS = 24;

    public function name(): string
    {
        return 'dummy';
    }

    public function createCharge(Payment $payment): array
    {
        $reference = match ($payment->method) {
            PaymentMethod::Qris => 'DEMO-QRIS-'.$payment->transaction_code,
            PaymentMethod::VirtualAccount => '8808'.str_pad((string) random_int(0, 999_999_999_999), 12, '0', STR_PAD_LEFT),
            PaymentMethod::Ewallet => 'DEMO-EW-'.substr(preg_replace('/\D/', '', $payment->transaction_code).random_int(100_000, 999_999), -10),
        };

        return [
            'reference' => $reference,
            'expires_at' => now()->addHours(self::EXPIRY_HOURS),
        ];
    }

    public function supportsSimulation(): bool
    {
        return true;
    }
}
