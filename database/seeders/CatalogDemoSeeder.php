<?php

namespace Database\Seeders;

use App\Enums\FreelancerAvailability;
use App\Enums\PackageTier;
use App\Enums\RoleName;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

                foreach ($data['portfolio'] as $portfolioData) {
                    $this->demoPortfolioItem($user, $categories[$portfolioData['category']], $portfolioData);
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
        $slug = Str::slug($data['title']);
        $service = Service::where('slug', $slug)->firstOrNew();
        $service->slug = $slug;
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
     * @param  array<string, mixed>  $data
     */
    private function demoPortfolioItem(User $user, int $categoryId, array $data): void
    {
        $path = "portfolio/{$user->id}/demo-".Str::slug($data['title']).'.png';

        if (! Storage::disk(PortfolioItem::DISK)->exists($path)) {
            Storage::disk(PortfolioItem::DISK)->put($path, $this->placeholderImage(...$data['colors']));
        }

        $item = PortfolioItem::firstOrNew(['user_id' => $user->id, 'title' => $data['title']]);
        $item->fill(['category_id' => $categoryId, 'description' => $data['description']]);
        $item->image_path = $path;
        $item->save();
    }

    /**
     * Gambar placeholder PNG (gradien + lingkaran) dibuat dengan GD, tanpa unduhan dari internet.
     */
    private function placeholderImage(string $from, string $to): string
    {
        [$width, $height] = [1200, 900];
        $image = imagecreatetruecolor($width, $height);
        [$r1, $g1, $b1] = sscanf($from, '#%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf($to, '#%02x%02x%02x');

        for ($y = 0; $y < $height; $y++) {
            $t = $y / $height;
            $color = imagecolorallocate($image, (int) ($r1 + ($r2 - $r1) * $t), (int) ($g1 + ($g2 - $g1) * $t), (int) ($b1 + ($b2 - $b1) * $t));
            imageline($image, 0, $y, $width, $y, $color);
        }

        $white = imagecolorallocatealpha($image, 255, 255, 255, 105);
        imagefilledellipse($image, 980, 160, 520, 520, $white);
        imagefilledellipse($image, 180, 820, 640, 640, $white);
        imagefilledrectangle($image, 380, 300, 820, 600, imagecolorallocatealpha($image, 255, 255, 255, 80));

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
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
                'portfolio' => [
                    ['title' => 'Obby Candy Land 40 Stage', 'category' => 'roblox-map', 'description' => 'Map obby bertema permen untuk komunitas Roblox sekolah.', 'colors' => ['#7c3aed', '#1a2040']],
                    ['title' => 'Tycoon Pabrik Roti', 'category' => 'roblox-map', 'description' => 'Game tycoon dengan sistem upgrade dan leaderboard.', 'colors' => ['#4f46e5', '#10152d']],
                ],
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
                'portfolio' => [
                    ['title' => 'Website Toko Kue Rumahan', 'category' => 'website-development', 'description' => 'Landing page dengan katalog produk dan pemesanan via WhatsApp.', 'colors' => ['#3d6bff', '#1a2040']],
                    ['title' => 'Portal Himpunan Mahasiswa', 'category' => 'website-development', 'description' => 'Website organisasi dengan berita dan agenda kegiatan.', 'colors' => ['#0ea5e9', '#262e52']],
                ],
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
                'portfolio' => [
                    ['title' => 'Poster Festival Musik Kampus', 'category' => 'poster-graphic-design', 'description' => 'Poster utama dan turunan media sosial untuk festival musik.', 'colors' => ['#c026d3', '#1a2040']],
                    ['title' => 'Identitas Visual Kedai Kopi', 'category' => 'poster-graphic-design', 'description' => 'Logo, menu, dan feed Instagram untuk kedai kopi lokal.', 'colors' => ['#f59e0b', '#36406b']],
                ],
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
