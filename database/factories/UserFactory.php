<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user has the Customer role.
     */
    public function customer(): static
    {
        return $this->withRole(RoleName::Customer);
    }

    /**
     * Indicate that the user is a Customer who also has a Freelancer role and profile.
     */
    public function freelancer(): static
    {
        return $this->withRole(RoleName::Customer)
            ->withRole(RoleName::Freelancer)
            ->has(FreelancerProfile::factory());
    }

    /**
     * Indicate that the user has the Admin role.
     */
    public function admin(): static
    {
        return $this->withRole(RoleName::Admin);
    }

    public function withRole(RoleName $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
