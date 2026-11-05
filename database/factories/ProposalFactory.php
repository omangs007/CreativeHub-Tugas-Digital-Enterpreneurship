<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'freelancer_id' => User::factory()->freelancer(),
            'price' => 500_000,
            'delivery_days' => 7,
            'revisions' => 2,
            'message' => fake()->paragraph(4),
            'status' => ProposalStatus::Pending,
        ];
    }

    public function status(ProposalStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
