<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\ProposalStatus;
use App\Models\Order;
use App\Models\Project;
use App\Models\Proposal;
use App\Support\CommissionCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Order default bersumber dari proposal yang diterima. Untuk order dari paket
 * gunakan OrderPlacement agar snapshot sesuai aturan.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'proposal_id' => Proposal::factory()->status(ProposalStatus::Accepted),
            'project_id' => fn (array $attributes) => Proposal::find($attributes['proposal_id'])->project_id,
            'customer_id' => fn (array $attributes) => Project::find($attributes['project_id'])->customer_id,
            'freelancer_id' => fn (array $attributes) => Proposal::find($attributes['proposal_id'])->freelancer_id,
            'title' => fn (array $attributes) => Project::find($attributes['project_id'])->title,
            'scope_description' => fake()->paragraph(),
            'delivery_days' => 7,
            'revisions_allowed' => 2,
            'revisions_used' => 0,
            'currency' => 'IDR',
            'subtotal_amount' => 500_000,
            'total_amount' => 500_000,
            'commission_rate_bps' => 1000,
            'commission_amount' => 50_000,
            'freelancer_amount' => 450_000,
            'status' => OrderStatus::PendingPayment,
        ];
    }

    public function total(int $total, int $rateBps = 1000): static
    {
        $split = CommissionCalculator::split($total, $rateBps);

        return $this->state(fn (array $attributes) => [
            'subtotal_amount' => $total,
            'total_amount' => $total,
            'commission_rate_bps' => $split['rate_bps'],
            'commission_amount' => $split['commission'],
            'freelancer_amount' => $split['freelancer'],
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'paid_at' => in_array($status, [OrderStatus::PendingPayment, OrderStatus::Cancelled], true) ? null : now(),
        ]);
    }
}
