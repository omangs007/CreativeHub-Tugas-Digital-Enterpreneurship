<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProposalStatus;
use App\Enums\RoleName;
use App\Models\Order;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use App\Notifications\ActivityNotification;
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
     * Customer memesan paket layanan. Harga dan scope diambil dari database (bukan dari
     * browser) di dalam transaksi dengan paket terkunci. Klik ganda tidak membuat order
     * ganda: order menunggu pembayaran yang sudah ada untuk paket yang sama dipakai ulang.
     *
     * @throws ValidationException
     */
    public function orderPackage(ServicePackage $package, User $customer, ?string $customerNote = null): Order
    {
        return DB::transaction(function () use ($package, $customer, $customerNote) {
            $package = ServicePackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
            $service = Service::publiclyVisible()->whereKey($package->service_id)->first();

            if (! $service || ! $package->is_active) {
                throw ValidationException::withMessages(['package' => __('Paket ini sedang tidak tersedia untuk dipesan.')]);
            }

            if ($service->user_id === $customer->id) {
                throw ValidationException::withMessages(['package' => __('Anda tidak dapat memesan layanan milik sendiri.')]);
            }

            if (! $customer->hasRole(RoleName::Customer)) {
                throw ValidationException::withMessages(['package' => __('Hanya akun customer yang dapat memesan layanan.')]);
            }

            $existing = Order::where('customer_id', $customer->id)
                ->where('service_package_id', $package->id)
                ->where('status', OrderStatus::PendingPayment)
                ->first();

            if ($existing) {
                return $existing;
            }

            $scope = $package->description;
            $customerNote = trim((string) $customerNote);

            if ($customerNote !== '') {
                $scope .= "\n\n".__('Catatan customer:')."\n".$customerNote;
            }

            $split = CommissionCalculator::split($package->price);

            $order = new Order;
            $order->order_number = Order::generateOrderNumber();
            $order->customer_id = $customer->id;
            $order->freelancer_id = $service->user_id;
            $order->service_id = $service->id;
            $order->service_package_id = $package->id;
            $order->title = $service->title;
            $order->package_tier = $package->tier->value;
            $order->package_name = $package->name;
            $order->scope_description = $scope;
            $order->delivery_days = $package->delivery_days;
            $order->revisions_allowed = $package->revisions;
            $order->subtotal_amount = $package->price;
            $order->total_amount = $package->price;
            $order->commission_rate_bps = $split['rate_bps'];
            $order->commission_amount = $split['commission'];
            $order->freelancer_amount = $split['freelancer'];
            $order->status = OrderStatus::PendingPayment;
            $order->save();

            $order->recordHistory(null, OrderStatus::PendingPayment, $customer, __('Order dibuat dari paket :tier.', ['tier' => $package->tier->label()]));

            return $order;
        });
    }

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

            $rejected = $project->proposals()
                ->whereKeyNot($proposal->id)
                ->where('status', ProposalStatus::Pending)
                ->with('freelancer')
                ->get();

            Proposal::whereKey($rejected->modelKeys())
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

            ActivityNotification::send($proposal->freelancer, __('Penawaran diterima'),
                __('Penawaran Anda untuk :title diterima. Pekerjaan dimulai setelah customer membayar.', ['title' => $project->title]),
                'orders.show', $order);

            $rejected->each(fn (Proposal $other) => ActivityNotification::send($other->freelancer, __('Penawaran tidak dipilih'),
                __('Customer memilih penawaran lain untuk :title.', ['title' => $project->title]),
                'freelancer.proposals.index'));

            return $order;
        });
    }
}
