<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Detail transaksi dan struk hanya untuk customer pemilik dan admin.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->isAdmin() || $payment->customer_id === $user->id;
    }

    /**
     * Simulasi hasil pembayaran hanya oleh customer pemilik (bukan admin, bukan freelancer).
     */
    public function simulate(User $user, Payment $payment): bool
    {
        return $payment->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
