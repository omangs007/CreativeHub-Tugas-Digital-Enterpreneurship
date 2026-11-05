<?php

namespace App\Payments;

use App\Models\Payment;

/**
 * Kontrak penyedia pembayaran. MVP hanya memakai DummyPaymentGateway.
 * Integrasi penyedia sungguhan (misalnya Midtrans) dapat ditambahkan dengan
 * implementasi baru tanpa mengubah alur order. Status akhir pembayaran sungguhan
 * harus datang dari notifikasi server-ke-server yang terverifikasi, bukan dari
 * redirect browser.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Membuat tagihan dan mengembalikan referensi/instruksi pembayaran.
     *
     * @return array{reference: string, expires_at: \DateTimeInterface}
     */
    public function createCharge(Payment $payment): array;

    /**
     * Apakah gateway mendukung simulasi hasil pembayaran dari UI (khusus mode demo).
     */
    public function supportsSimulation(): bool;
}
