<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProposalStatus;
use App\Models\Order;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Support\CommissionCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembuatan order dari sumber yang sah. Seluruh harga dan komisi dihitung di server
 * dan di-snapshot ke order.
 */
class OrderPlacement
{
    /**
     * Customer menerima satu proposal: proposal lain ditolak, proyek menjadi dikerjakan,
     * dan order menunggu pembayaran dibuat. Baris proyek dikunci agar penerimaan ganda
     * (misalnya dua klik bersamaan) tidak menghasilkan dua order.
     *
     * @throws ValidationException
     */
    public function acceptProposal(Proposal $proposal, User $customer): Order
    {
        return DB::transaction(function () use ($proposal, $customer) {
            $project = Project::whereKey($proposal->project_id)->lockForUpdate()->firstOrFail();
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();

            if ($project->customer_id !== $customer->id) {
                throw ValidationException::withMessages(['proposal' => __('Anda bukan pemilik proyek ini.')]);
            }

            if ($project->status !== ProjectStatus::Open) {
                throw ValidationException::withMessages(['proposal' => __('Proyek ini sudah tidak menerima penawaran.')]);
            }

            if ($proposal->status !== ProposalStatus::Pending) {
                throw ValidationException::withMessages(['proposal' => __('Penawaran ini tidak dapat diterima.')]);
            }

            $proposal->status = ProposalStatus::Accepted;
            $proposal->save();

            $project->proposals()
                ->whereKeyNot($proposal->id)
                ->where('status', ProposalStatus::Pending)
                ->update(['status' => ProposalStatus::Rejected, 'updated_at' => now()]);

            $project->status = ProjectStatus::InProgress;
            $project->save();

            $split = CommissionCalculator::split($proposal->price);

            $order = new Order;
            $order->order_number = Order::generateOrderNumber();
            $order->customer_id = $project->customer_id;
            $order->freelancer_id = $proposal->freelancer_id;
            $order->project_id = $project->id;
            $order->proposal_id = $proposal->id;
            $order->title = $project->title;
            $order->scope_description = $project->description."\n\n".__('Penawaran freelancer:')."\n".$proposal->message;
            $order->delivery_days = $proposal->delivery_days;
            $order->revisions_allowed = $proposal->revisions;
            $order->subtotal_amount = $proposal->price;
            $order->total_amount = $proposal->price;
            $order->commission_rate_bps = $split['rate_bps'];
            $order->commission_amount = $split['commission'];
            $order->freelancer_amount = $split['freelancer'];
            $order->status = OrderStatus::PendingPayment;
            $order->save();

            $order->recordHistory(null, OrderStatus::PendingPayment, $customer, __('Order dibuat dari penerimaan penawaran.'));

            return $order;
        });
    }
}
