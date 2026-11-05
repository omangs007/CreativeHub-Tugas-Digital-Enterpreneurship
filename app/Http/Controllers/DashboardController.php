<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProposalStatus;
use App\Enums\RoleName;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard ringkas sesuai peran user login. Semua angka dihitung dari data milik user sendiri.
 */
class DashboardController extends Controller
{
    /** Status order yang sudah dibayar dan belum selesai/batal. */
    public const ACTIVE_PAID = [OrderStatus::Paid, OrderStatus::InProgress, OrderStatus::Delivered, OrderStatus::RevisionRequested];

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'customer' => $user->hasRole(RoleName::Customer) ? $this->customerData($user) : null,
            'freelancer' => $user->hasRole(RoleName::Freelancer) ? $this->freelancerData($user) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function customerData(User $user): array
    {
        return [
            'statusCounts' => $this->countByStatus($user->customerOrders()->getQuery()),
            'toPay' => $user->customerOrders()->where('status', OrderStatus::PendingPayment)->latest()->limit(5)->get(),
            'toReview' => $user->customerOrders()->where('status', OrderStatus::Delivered)->with('freelancer')->latest('delivered_at')->limit(5)->get(),
            'openProjects' => $user->projects()
                ->where('status', ProjectStatus::Open)
                ->withCount(['proposals as pending_proposals_count' => fn ($q) => $q->where('status', ProposalStatus::Pending)])
                ->latest()
                ->limit(5)
                ->get(),
            'recentPayments' => Payment::where('customer_id', $user->id)->with('order')->latest()->limit(5)->get(),
            'totalSpent' => (int) Payment::where('customer_id', $user->id)->where('status', PaymentStatus::Success)->sum('amount'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function freelancerData(User $user): array
    {
        return [
            'statusCounts' => $this->countByStatus($user->freelancerOrders()->getQuery()),
            'toDeliver' => $user->freelancerOrders()
                ->whereIn('status', [OrderStatus::InProgress, OrderStatus::RevisionRequested])
                ->with('customer')
                ->oldest('paid_at')
                ->limit(5)
                ->get(),
            'pendingProposals' => $user->proposals()->where('status', ProposalStatus::Pending)->count(),
            'serviceCounts' => $user->services()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($n) => (int) $n),
            'earnings' => self::earningsSummary($user),
        ];
    }

    /**
     * Ringkasan pendapatan freelancer (pencatatan simulasi, bukan payout).
     *
     * @return array{completed: int, in_progress: int, commission: int, completed_count: int}
     */
    public static function earningsSummary(User $user): array
    {
        $completed = $user->freelancerOrders()->where('status', OrderStatus::Completed);
        $active = $user->freelancerOrders()->whereIn('status', self::ACTIVE_PAID);

        return [
            'completed' => (int) (clone $completed)->sum('freelancer_amount'),
            'completed_count' => (clone $completed)->count(),
            'in_progress' => (int) $active->sum('freelancer_amount'),
            'commission' => (int) $user->freelancerOrders()
                ->whereIn('status', [...self::ACTIVE_PAID, OrderStatus::Completed])
                ->sum('commission_amount'),
        ];
    }

    /**
     * @param  Builder<Order>  $query
     * @return array<string, int>
     */
    private function countByStatus(Builder $query): array
    {
        return $query->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
