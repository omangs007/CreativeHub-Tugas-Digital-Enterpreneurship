<?php

namespace Database\Factories;

use App\Enums\FreelancerAvailability;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreelancerProfile>
 */
class FreelancerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'headline' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'skills' => fake()->randomElements(['Roblox Studio', 'Laravel', 'Figma', 'Photoshop', 'Illustrator'], 2),
            'availability' => FreelancerAvailability::Available,
        ];
    }
}
