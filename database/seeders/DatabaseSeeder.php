<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Role dibuat oleh migration. Akun admin dibuat dengan `php artisan app:create-admin`.
     * Data demo katalog: `php artisan db:seed --class=CatalogDemoSeeder`.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);
    }
}
