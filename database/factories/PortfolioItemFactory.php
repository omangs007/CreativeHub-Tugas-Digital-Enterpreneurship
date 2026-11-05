<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioItem>
 */
class PortfolioItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->freelancer(),
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => fake()->sentence(12),
            'image_path' => 'portfolio/contoh-'.fake()->uuid().'.png',
            'project_url' => null,
        ];
    }
}
