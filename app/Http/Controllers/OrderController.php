<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pesanan yang melibatkan user login, baik sebagai customer maupun freelancer.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $request->query('sebagai') === 'freelancer' ? 'freelancer' : 'customer';

        $orders = Order::query()
            ->where($role === 'freelancer' ? 'freelancer_id' : 'customer_id', $user->id)
            ->with(['customer', 'freelancer'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'role' => $role,
            'hasFreelancerOrders' => Order::where('freelancer_id', $user->id)->exists(),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('orders.show', [
            'order' => $order->load(['customer', 'freelancer', 'statusHistories.changedBy', 'service', 'project', 'payments' => fn ($q) => $q->latest(), 'deliveries.freelancer', 'revisions.requester']),
        ]);
    }
}
