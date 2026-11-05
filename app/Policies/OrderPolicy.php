<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya customer dan freelancer yang terlibat, serta admin, yang dapat melihat order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->involves($user);
    }

    public function pay(User $user, Order $order): bool
    {
        return $order->customer_id === $user->id && $order->status === OrderStatus::PendingPayment;
    }

    public function deliver(User $user, Order $order): bool
    {
        return $order->freelancer_id === $user->id
            && in_array($order->status, [OrderStatus::InProgress, OrderStatus::RevisionRequested], true);
    }

    /**
     * Customer pemilik meninjau hasil: minta revisi atau selesaikan.
     */
    public function review(User $user, Order $order): bool
    {
        return $order->customer_id === $user->id && $order->status === OrderStatus::Delivered;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Order $order): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
