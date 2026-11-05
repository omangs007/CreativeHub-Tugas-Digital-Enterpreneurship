<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Kategori wajib CreativeHub. Aman dijalankan berulang kali (idempoten).
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'roblox-map',
                'name' => 'Roblox Map',
                'description' => 'Desain map, obby, dan dunia game Roblox siap main.',
                'icon' => 'gamepad',
            ],
            [
                'slug' => 'website-development',
                'name' => 'Website Development',
                'description' => 'Landing page, company profile, hingga aplikasi web.',
                'icon' => 'code',
            ],
            [
                'slug' => 'poster-graphic-design',
                'name' => 'Poster & Graphic Design',
                'description' => 'Poster, banner, logo, dan materi visual untuk promosi.',
                'icon' => 'palette',
            ],
        ];

        foreach ($categories as $order => $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'sort_order' => $order + 1],
            );
        }
    }
}
