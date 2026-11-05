<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ringkasan pendapatan freelancer. Pembagian komisi hanya pencatatan simulasi;
 * tidak ada payout uang sungguhan.
 */
class EarningsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $orders = $user->freelancerOrders()
            ->whereIn('status', [...DashboardController::ACTIVE_PAID, OrderStatus::Completed])
            ->with('customer')
            ->latest('paid_at')
            ->paginate(20);

        return view('freelancer.earnings', [
            'orders' => $orders,
            'summary' => DashboardController::earningsSummary($user),
        ]);
    }
}
