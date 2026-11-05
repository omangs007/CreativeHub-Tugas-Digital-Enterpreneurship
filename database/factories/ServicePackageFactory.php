<?php

namespace Database\Factories;

use App\Enums\PackageTier;
use App\Models\Service;
use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePackage>
 */
class ServicePackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'tier' => PackageTier::Basic,
            'name' => 'Basic',
            'description' => fake()->sentence(10),
            'price' => fake()->numberBetween(5, 100) * 10_000,
            'delivery_days' => fake()->numberBetween(1, 14),
            'revisions' => fake()->numberBetween(0, 3),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
