<?php

namespace Database\Factories;

use App\Enums\PackageTier;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'user_id' => User::factory()->freelancer(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'description' => fake()->paragraphs(2, true),
            'status' => ServiceStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function status(ServiceStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'published_at' => $status === ServiceStatus::Published ? now() : null,
        ]);
    }

    /**
     * Tambahkan paket Basic, Standard, dan Premium yang aktif.
     */
    public function withPackages(int $basicPrice = 150_000): static
    {
        return $this->has(
            ServicePackage::factory()
                ->count(3)
                ->state(new Sequence(
                    ['tier' => PackageTier::Basic, 'name' => 'Basic', 'price' => $basicPrice, 'delivery_days' => 7, 'revisions' => 1],
                    ['tier' => PackageTier::Standard, 'name' => 'Standard', 'price' => $basicPrice * 2, 'delivery_days' => 5, 'revisions' => 2],
                    ['tier' => PackageTier::Premium, 'name' => 'Premium', 'price' => $basicPrice * 4, 'delivery_days' => 3, 'revisions' => 5],
                )),
            'packages'
        );
    }
}
