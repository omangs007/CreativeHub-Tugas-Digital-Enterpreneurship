<?php

namespace Database\Seeders;

use App\Enums\FreelancerAvailability;
use App\Enums\PackageTier;
use App\Enums\RoleName;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DATA DEMO untuk presentasi katalog, bukan data produksi.
 *
 * Jalankan: php artisan db:seed --class=CatalogDemoSeeder
 * Aman dijalankan berulang. Password akun demo dibuat acak dan hanya ditampilkan
 * saat akun pertama kali dibuat.
 */
class CatalogDemoSeeder extends Seeder
{
    public const EMAIL_DOMAIN = 'demo.creativehub.test';

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $categories = Category::pluck('id', 'slug');

        DB::transaction(function () use ($categories) {
            foreach ($this->freelancers() as $data) {
                $user = $this->demoFreelancer($data);

                foreach ($data['services'] as $serviceData) {
                    $this->demoService($user, $categories[$serviceData['category']], $serviceData);
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function demoFreelancer(array $data): User
    {
        $email = $data['username'].'@'.self::EMAIL_DOMAIN;
        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = Str::password(16, symbols: false);

            $user = User::create(['name' => $data['name'], 'email' => $email, 'password' => $password]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->command?->line("  Akun demo: {$email}  password: {$password}");
        }

        $user->assignRole(RoleName::Customer);
        $user->assignRole(RoleName::Freelancer);

        $user->freelancerProfile()->updateOrCreate([], [
            'headline' => $data['headline'],
            'bio' => $data['bio'],
            'skills' => $data['skills'],
            'availability' => FreelancerAvailability::Available,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function demoService(User $user, int $categoryId, array $data): void
    {
        $service = Service::firstOrNew(['slug' => Str::slug($data['title'])]);
        $service->fill([
            'category_id' => $categoryId,
            'title' => $data['title'],
            'description' => $data['description'],
        ]);
        $service->user_id = $user->id;
        $service->status = $data['status'] ?? ServiceStatus::Published;
        $service->published_at = $service->status === ServiceStatus::Published
            ? ($service->published_at ?? now()->subDays($data['days_ago'] ?? 0))
            : null;
        $service->save();

        foreach ($data['packages'] as $index => [$price, $days, $revisions, $description]) {
            $tier = PackageTier::cases()[$index];

            $service->packages()->updateOrCreate(['tier' => $tier], [
                'name' => $tier->label(),
                'description' => $description,
                'price' => $price,
                'delivery_days' => $days,
                'revisions' => $revisions,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function freelancers(): array
    {
        return [
            [
                'username' => 'raka',
                'name' => 'Raka Pratama',
                'headline' => 'Roblox Map Builder & Scripter',
                'bio' => "Membangun map Roblox sejak 2021.\nFokus pada obby, tycoon, dan roleplay dengan performa ringan untuk perangkat mobile.",
                'skills' => ['Roblox Studio', 'Luau', 'Blender'],
                'services' => [
                    [
                        'category' => 'roblox-map',
                        'title' => 'Desain Map Obby Roblox Custom',
                        'description' => "Map obby dengan tema sesuai keinginanmu: hutan, luar angkasa, candy land, dan lainnya.\nSudah termasuk checkpoint, kill brick, dan uji main sebelum diserahkan.",
                        'days_ago' => 2,
                        'packages' => [
                            [150_000, 7, 1, '10 stage obby dengan satu tema dan checkpoint.'],
                            [350_000, 10, 2, '25 stage, leaderboard waktu, dan efek partikel.'],
                            [750_000, 14, 4, '50 stage, sistem gamepass skip stage, dan musik latar.'],
                        ],
                    ],
                    [
                        'category' => 'roblox-map',
                        'title' => 'Map Roleplay Kota untuk Roblox',
                        'description' => "Kota roleplay lengkap dengan jalan, rumah, toko, dan sekolah.\nCocok untuk komunitas atau grup Roblox yang ingin punya game sendiri.",
                        'days_ago' => 6,
                        'packages' => [
                            [400_000, 10, 1, 'Satu distrik kota dengan 6 bangunan bisa dimasuki.'],
                            [900_000, 14, 2, 'Dua distrik, sistem pekerjaan sederhana, dan kendaraan.'],
                            [1_800_000, 21, 3, 'Kota lengkap, sistem uang, rumah bisa dibeli, dan admin panel.'],
                        ],
                    ],
                ],
            ],
            [
                'username' => 'nadia',
                'name' => 'Nadia Putri',
                'headline' => 'Laravel & Tailwind Web Developer',
                'bio' => 'Web developer yang membantu UMKM dan organisasi kampus tampil profesional secara online.',
                'skills' => ['Laravel', 'Tailwind CSS', 'PostgreSQL'],
                'services' => [
                    [
                        'category' => 'website-development',
                        'title' => 'Landing Page Responsif untuk UMKM',
                        'description' => "Landing page satu halaman yang cepat, responsif, dan siap dipakai untuk promosi.\nTermasuk tombol WhatsApp dan formulir kontak.",
                        'days_ago' => 1,
                        'packages' => [
                            [300_000, 5, 1, 'Satu halaman dengan 4 section dan tombol WhatsApp.'],
                            [650_000, 7, 2, 'Hingga 7 section, formulir kontak, dan optimasi SEO dasar.'],
                            [1_200_000, 10, 3, 'Desain custom, animasi ringan, dan panduan update konten.'],
                        ],
                    ],
                    [
                        'category' => 'website-development',
                        'title' => 'Website Company Profile Lengkap',
                        'description' => 'Website multi-halaman untuk perusahaan atau organisasi, lengkap dengan halaman layanan, portofolio, dan kontak.',
                        'days_ago' => 9,
                        'packages' => [
                            [1_500_000, 14, 2, 'Lima halaman statis dengan desain responsif.'],
                            [3_000_000, 21, 3, 'Hingga 10 halaman dan panel admin untuk berita.'],
                            [5_500_000, 30, 5, 'Panel admin lengkap, multi bahasa, dan pelatihan penggunaan.'],
                        ],
                    ],
                ],
            ],
            [
                'username' => 'dimas',
                'name' => 'Dimas Saputra',
                'headline' => 'Graphic Designer — Poster & Social Media',
                'bio' => 'Desainer grafis untuk event kampus, brand lokal, dan kebutuhan promosi media sosial.',
                'skills' => ['Adobe Illustrator', 'Photoshop', 'Figma'],
                'services' => [
                    [
                        'category' => 'poster-graphic-design',
                        'title' => 'Desain Poster Event & Promosi',
                        'description' => "Poster menarik untuk seminar, lomba, konser, atau promo produk.\nFile siap cetak (PDF) dan versi media sosial (PNG).",
                        'days_ago' => 3,
                        'packages' => [
                            [75_000, 3, 1, 'Satu desain poster ukuran A4 atau feed Instagram.'],
                            [150_000, 3, 3, 'Poster A3 + versi story Instagram.'],
                            [300_000, 5, 5, 'Poster, banner, dan tiga turunan media sosial.'],
                        ],
                    ],
                    [
                        'category' => 'poster-graphic-design',
                        'title' => 'Paket Desain Feed Instagram',
                        'description' => 'Desain konten feed Instagram yang konsisten dengan identitas brand kamu.',
                        'days_ago' => 12,
                        'packages' => [
                            [120_000, 4, 1, 'Tiga desain feed dengan template yang bisa dipakai ulang.'],
                            [250_000, 6, 2, 'Sembilan desain feed membentuk grid yang serasi.'],
                            [500_000, 10, 3, 'Delapan belas desain feed + lima desain story.'],
                        ],
                    ],
                    [
                        'category' => 'poster-graphic-design',
                        'title' => 'Desain Logo Minimalis',
                        'description' => 'Contoh layanan berstatus draf: tidak tampil di katalog publik.',
                        'status' => ServiceStatus::Draft,
                        'packages' => [
                            [200_000, 5, 2, 'Dua konsep logo.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
