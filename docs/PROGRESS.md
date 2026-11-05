# CreativeHub — Progress Log

## Hari 1 — Foundation & Authentication

**Tanggal pengerjaan:** 5 November 2026
**Environment:** Localhost (Windows 11 Pro)
**Status:** Selesai dan disetujui (5 November 2026)

### Keputusan yang diambil

| Keputusan | Alasan |
|---|---|
| Database **PostgreSQL 17** (bukan MariaDB seperti di `CLAUDE_CreativeHub_Revisi.md`) | Instruksi terbaru prompt Hari 1 memprioritaskan PostgreSQL. Konsekuensinya, komputer demo juga perlu PostgreSQL. |
| Laravel 13 + Breeze 2.4 **stack Blade** | Stack Livewire milik Breeze mengunci `livewire/livewire:^3.6`, sedangkan Filament 5 membutuhkan Livewire 4. Stack Blade tidak memiliki konflik ini. Livewire 4 tetap terpasang lewat Filament dan bisa dipakai untuk komponen interaktif. |
| Filament 5 untuk admin panel di `/admin` | Akses dibatasi oleh `User::canAccessPanel()` dan hanya untuk role Admin. |
| Tailwind CSS 4 via `@tailwindcss/vite` | Breeze memasang Tailwind 3 + PostCSS; seluruhnya dimigrasikan ke v4 agar konsisten dengan skeleton Laravel 13. |
| Role disimpan di tabel `roles` + pivot `user_roles` | Satu akun boleh memiliki Customer dan Freelancer sekaligus. Guest = pengunjung yang belum login (tidak disimpan). |
| Data role diisi di **migration**, bukan seeder | Role wajib ada agar registrasi berjalan, termasuk di database test. |
| Admin hanya dibuat via `php artisan app:create-admin` | Registrasi publik tidak bisa memberi role Admin. |
| Session & cache `database`, queue `sync` | Sederhana untuk development, tanpa Redis atau worker. |
| Zona waktu `Asia/Jakarta`, locale `id` | Sesuai blueprint. |
| Test memakai database PostgreSQL terpisah `creativehub_test` | Data development tidak terhapus saat test (`RefreshDatabase`). |

### Versi software terverifikasi

| Komponen | Versi |
|---|---|
| PHP | 8.4.26 (ekstensi `pdo_pgsql` dan `pgsql` diaktifkan) |
| Composer | 2.9.2 |
| Node.js / npm | 24.12.0 / 11.6.2 |
| PostgreSQL | 17.11 (service `postgresql-x64-17`, port 5432) |
| Laravel Framework | 13.40.0 |
| Laravel Breeze | 2.4.2 |
| Livewire | 4.4.8 |
| Filament | 5.11.0 |
| Tailwind CSS | 4.3 |
| Vite | 7 |

### Fitur yang dikerjakan

- **Setup:** proyek Laravel, koneksi PostgreSQL, Vite + Tailwind, Filament panel `/admin`.
- **Database:**
  - `roles`: `name` unique, berisi Customer, Freelancer, Admin.
  - `user_roles`: primary key gabungan `(user_id, role_id)`, index `role_id`. FK `user_id` cascade, FK `role_id` restrict.
  - `freelancer_profiles`: `user_id` unique (satu profil per akun), FK cascade, `skills` jsonb, `availability` ber-index.
- **Authentication (Breeze):** register, login, logout, lupa/reset password, ubah password, profil, hapus akun. Password di-hash dengan bcrypt bawaan Laravel.
- **Role assignment:**
  - Registrasi publik selalu memberi role **Customer** di dalam transaksi DB. Input `role`, `roles`, dan `role_id` dari browser diabaikan.
  - Customer dapat mengaktifkan role **Freelancer** dari halaman profil (`POST /profile/freelancer`). Aksi ini membuat profil freelancer kosong, dijaga oleh `FreelancerProfilePolicy` (hanya sekali per akun).
  - **Admin** hanya lewat command `app:create-admin` (password diinput secara tersembunyi, divalidasi, email unik).
- **Authorization:**
  - `User::canAccessPanel()` membatasi Filament hanya untuk Admin. Diperiksa di server; customer/freelancer mendapat 403.
  - Middleware alias `role` (`EnsureUserHasRole`), contoh: `->middleware(['auth', 'role:freelancer'])`.
  - `FreelancerProfilePolicy` (`create`, `update`).
  - Link "Panel Admin" di navigasi hanya tampil untuk Admin.
- **Bahasa Indonesia:** `lang/id.json` (teks UI Breeze), `lang/id/{auth,passwords,validation,pagination}.php`.

### File yang dibuat atau diubah

Dibuat oleh installer: skeleton Laravel 13, scaffolding Breeze Blade, `AdminPanelProvider` Filament.

Dibuat atau diubah secara khusus:

```text
.env.example                         (CreativeHub, pgsql, locale id, queue sync, tanpa secret)
config/app.php                       (timezone default Asia/Jakarta via APP_TIMEZONE)
phpunit.xml                          (test memakai pgsql creativehub_test)
vite.config.js                       (plugin @tailwindcss/vite)
package.json                         (Tailwind 4, postcss/autoprefixer dihapus)
resources/css/app.css                (sintaks Tailwind 4)
bootstrap/app.php                    (alias middleware role)
app/Enums/RoleName.php
app/Enums/FreelancerAvailability.php
app/Models/User.php                  (roles, freelancerProfile, hasRole, assignRole, isAdmin, FilamentUser)
app/Models/Role.php
app/Models/FreelancerProfile.php
app/Http/Middleware/EnsureUserHasRole.php
app/Policies/FreelancerProfilePolicy.php
app/Http/Controllers/FreelancerActivationController.php
app/Http/Controllers/Auth/RegisteredUserController.php   (otomatis role Customer)
app/Console/Commands/CreateAdminCommand.php
app/Providers/Filament/AdminPanelProvider.php            (brand CreativeHub Admin, warna violet)
database/migrations/2026_11_05_000001_create_roles_tables.php
database/migrations/2026_11_05_000002_create_freelancer_profiles_table.php
database/factories/UserFactory.php   (state customer/freelancer/admin)
database/factories/FreelancerProfileFactory.php
database/seeders/DatabaseSeeder.php  (user demo berpassword "password" dihapus)
routes/web.php                       (POST /profile/freelancer)
resources/views/layouts/navigation.blade.php             (link Panel Admin)
resources/views/profile/edit.blade.php
resources/views/profile/partials/account-roles.blade.php
lang/id.json, lang/id/*.php
tests/Feature/Auth/RegistrationTest.php                  (+3 test)
tests/Feature/Auth/AccessControlTest.php
tests/Feature/RoleMiddlewareTest.php
tests/Feature/FreelancerActivationTest.php
tests/Feature/CreateAdminCommandTest.php
docs/PROGRESS.md
```

Di luar proyek:

- `C:\php\8.4\php.ini`: `extension=pdo_pgsql` dan `extension=pgsql` diaktifkan (backup: `php.ini.bak`).

### Command yang dijalankan

```powershell
# Environment
php -v; composer --version; node -v; npm -v
winget install --id PostgreSQL.PostgreSQL.17 -e --silent --override "--mode unattended ..."
psql -U postgres -c "CREATE ROLE creativehub LOGIN PASSWORD '***'" `
     -c "CREATE DATABASE creativehub OWNER creativehub" `
     -c "CREATE DATABASE creativehub_test OWNER creativehub"

# Proyek
composer create-project laravel/laravel creativehub "^13.0"
composer require laravel/breeze:^2.4 --dev
php artisan breeze:install blade
npx @tailwindcss/upgrade@4 --force
npm uninstall @tailwindcss/postcss postcss
composer require filament/filament:^5.11 -W
php artisan filament:install --panels
php artisan migrate
php artisan migrate:fresh
npm run build
vendor/bin/pint
php artisan test
```

### Hasil testing

`php artisan test` (5 November 2026): **46 passed (120 assertions)**, PostgreSQL `creativehub_test`.

| Skenario wajib Hari 1 | Test |
|---|---|
| User berhasil register | `RegistrationTest::test_new_users_can_register` |
| Password tersimpan ter-hash | `RegistrationTest::test_new_users_are_stored_with_hashed_password` |
| User berhasil login | `AuthenticationTest::test_users_can_authenticate_using_the_login_screen` |
| Password salah ditolak | `AuthenticationTest::test_users_can_not_authenticate_with_invalid_password` |
| User berhasil logout | `AuthenticationTest::test_users_can_logout` |
| User baru otomatis Customer | `RegistrationTest::test_new_users_automatically_get_only_the_customer_role` |
| Registrasi publik tidak bisa jadi Admin | `RegistrationTest::test_public_registration_cannot_grant_admin_role` |
| Customer tidak dapat akses Admin Panel | `AccessControlTest::test_customers_cannot_access_admin_panel` (juga freelancer dan user tanpa role) |
| Admin dapat akses Admin Panel | `AccessControlTest::test_admins_can_access_admin_panel` |
| Guest diarahkan ke login | `AccessControlTest::test_guests_are_redirected_to_login_from_protected_pages`, `..._to_admin_login_from_admin_panel` |
| Middleware role | `RoleMiddlewareTest` (4 test) |
| Aktivasi freelancer | `FreelancerActivationTest` (4 test) |
| Command buat admin | `CreateAdminCommandTest` (3 test) |

Uji end-to-end manual via HTTP ke `http://127.0.0.1:8000` (`php artisan serve`):

| Langkah | Hasil |
|---|---|
| Register customer (dengan field `role=admin` disisipkan) | 302 → `/dashboard`, role tersimpan hanya `customer`, password berformat bcrypt `$2y$` |
| Customer membuka `/admin` | 403 |
| Login dengan password salah | 302 kembali ke `/login`, tidak terautentikasi |
| Login admin, lalu membuka `/admin` | 200 |
| Logout, lalu membuka `/dashboard` | 302 → `/login` |

Akun uji E2E dihapus setelah pengujian.

Lainnya: `vendor/bin/pint --test` passed, `npm run build` sukses, `php artisan migrate:fresh` sukses (5 migration).

### Error dan solusinya

| Error / masalah | Solusi |
|---|---|
| PostgreSQL belum terinstal, `psql` tidak dikenal | Instal PostgreSQL 17.11 via winget (unattended); password superuser dibuat acak dan disimpan di luar proyek. |
| Ekstensi `pdo_pgsql` tidak aktif | Aktifkan di `C:\php\8.4\php.ini` (backup dibuat). |
| Installer Laravel membuat `database/database.sqlite` dan migrate ke SQLite | File dihapus; `.env` dialihkan ke pgsql. |
| Stack Livewire Breeze (Livewire 3) konflik dengan Filament 5 (Livewire 4) | Gunakan stack Blade Breeze. |
| Tailwind 3 (Breeze) bercampur dengan `@tailwindcss/vite` 4 (skeleton) | Migrasi ke Tailwind 4 dengan `@tailwindcss/upgrade`, pakai plugin Vite, hapus `postcss.config.js`. |
| `Set-Content -Encoding utf8` (PowerShell 5.1) menambahkan BOM ke `config/app.php` | BOM dihapus. Hindari `Set-Content` untuk file PHP. |
| Pint melaporkan gaya import di stub Breeze | `vendor/bin/pint` dijalankan; test tetap lulus. |

### Cara menjalankan di localhost

```powershell
cd creativehub
composer install
npm install
copy .env.example .env      # isi DB_PASSWORD milik role creativehub
php artisan key:generate
php artisan migrate
php artisan app:create-admin   # buat akun admin (interaktif)
npm run build                  # atau: npm run dev (terminal terpisah)
php artisan serve --host=127.0.0.1 --port=8000
```

- Website: <http://127.0.0.1:8000>
- Admin panel: <http://127.0.0.1:8000/admin>

### Pekerjaan yang belum selesai / catatan

- Belum ada akun admin di database development. Jalankan `php artisan app:create-admin` dengan password pilihan Anda sendiri.
- Password database dan superuser `postgres` tersimpan di `C:\Users\admin\creativehub-postgres-superuser.txt`. Pindahkan ke password manager, lalu hapus file tersebut.
- Commit Hari 1 dibuat setelah persetujuan.
- Homepage `/` masih halaman bawaan Laravel. Layout, branding (font Plus Jakarta Sans/Inter, palet navy dan violet), dan dashboard per role belum dibuat.
- Status akun aktif/nonaktif (diblokir admin) belum ada; direncanakan bersama manajemen pengguna admin.
- Admin belum bisa mengelola user atau role dari Filament (belum ada Resource).
- Verifikasi email belum diwajibkan (`MustVerifyEmail` tidak aktif); mail driver `log`.
- Konflik dokumen yang perlu diputuskan: blueprint mewajibkan **pembayaran simulasi/dummy**, sedangkan prompt menyebut Midtrans. Rekomendasi: dummy adapter untuk MVP, Midtrans opsional.

### Rencana Hari 2 — Katalog jasa kreatif

1. Migration `categories`, `services`, `service_packages`. Harga BIGINT Rupiah, status publikasi, slug unik, index.
2. Seeder kategori: Roblox Map, Website Development, Poster/Graphic Design.
3. Layout publik CreativeHub (navbar, footer, font, palet warna) dan homepage dasar.
4. Halaman `/services` (katalog), `/categories/{slug}`, `/services/{slug}` (detail + paket Basic/Standard/Premium). Hanya layanan published yang tampil.
5. Feature test: guest dapat melihat katalog; layanan draft tidak tampil; detail layanan menampilkan paket aktif.
6. Update `docs/PROGRESS.md`.

---

## Hari 2 — Katalog Jasa Kreatif

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-2-katalog`, di-merge ke `master`
**Status:** Selesai dan disetujui (5 November 2026)

### Fitur yang dikerjakan

- **Database:**
  - `categories`: slug unik, `is_active`, `sort_order`, ikon.
  - `services`: FK freelancer (cascade) dan kategori (restrict), slug unik, status `draft | pending_review | published | rejected` dengan CHECK constraint, index `(status, category_id)`.
  - `service_packages`: harga BIGINT Rupiah dengan CHECK `price > 0`, CHECK `delivery_days > 0`, CHECK tier, unique `(service_id, tier)`.
- **Model:**
  - `Category`, `Service`, `ServicePackage`, serta enum `ServiceStatus` dan `PackageTier`.
  - `status` dan `published_at` pada `Service` tidak fillable; status hanya diubah oleh alur moderasi.
  - Scope `Service::publiclyVisible()`: status published, kategori aktif, dan minimal satu paket aktif.
  - Scope `forCatalogCard()`: eager load relasi, harga mulai (min harga paket aktif), dan waktu tercepat.
- **Seeder:**
  - `CategorySeeder` idempoten (Roblox Map, Website Development, Poster & Graphic Design), dipanggil dari `DatabaseSeeder`.
  - `CatalogDemoSeeder` (**data demo**, dijalankan terpisah): 3 akun freelancer demo dengan password acak, 6 layanan published, dan 1 layanan draf.
- **Halaman publik:**
  - `/`: hero dengan pencarian, kategori beserta jumlah layanan, 6 layanan terbaru, CTA, dan empty state.
  - `/services`: pencarian judul (`ILIKE`, parameter `q`), filter kategori (parameter `kategori`), paginasi 12 per halaman, empty state.
  - `/categories/{slug}`: 404 jika kategori nonaktif atau tidak ada.
  - `/services/{slug}`: detail layanan, info freelancer, tab paket Basic/Standard/Premium (Alpine). Hanya paket aktif yang tampil. Layanan yang tidak published memberi 404.
  - Tombol "Pesan Paket" dinonaktifkan dengan keterangan "Pemesanan dan checkout segera tersedia" (checkout dijadwalkan nanti).
- **UI/brand:**
  - Font Plus Jakarta Sans, palet navy `ink-*` dengan aksen violet dan `electric-*`, logo CreativeHub baru.
  - Ikon Lucide inline (`<x-lucide>`).
  - Layout publik responsif dengan menu mobile; layout Breeze memakai font yang sama dan memiliki link "Katalog Jasa".
- **Helper:**
  - `App\Support\Rupiah::format()` dan directive Blade `@rupiah(...)`, contoh `Rp1.250.000`.
  - Tanggal memakai `translatedFormat('d F Y')`, contoh `03 November 2026`.
- **Pencegahan N+1:** `Model::preventLazyLoading()` aktif di luar production.

### File yang dibuat atau diubah

```text
app/Enums/ServiceStatus.php, app/Enums/PackageTier.php
app/Models/Category.php, Service.php, ServicePackage.php, User.php (relasi services)
app/Http/Controllers/HomeController.php, ServiceController.php, CategoryController.php
app/Support/Rupiah.php
app/View/Components/PublicLayout.php
app/Providers/AppServiceProvider.php     (preventLazyLoading, @rupiah, kategori untuk navbar)
database/migrations/2026_11_05_000003_create_categories_table.php
database/migrations/2026_11_05_000004_create_services_table.php
database/migrations/2026_11_05_000005_create_service_packages_table.php
database/factories/CategoryFactory.php, ServiceFactory.php, ServicePackageFactory.php
database/seeders/CategorySeeder.php, CatalogDemoSeeder.php, DatabaseSeeder.php
routes/web.php
resources/css/app.css                    (token warna, font, x-cloak)
resources/views/layouts/public.blade.php
resources/views/layouts/app.blade.php, guest.blade.php, navigation.blade.php
resources/views/home.blade.php
resources/views/services/index.blade.php, services/show.blade.php
resources/views/categories/show.blade.php
resources/views/components/lucide.blade.php, service-card.blade.php, service-cover.blade.php,
    service-grid.blade.php, empty-state.blade.php, application-logo.blade.php
resources/views/welcome.blade.php        (dihapus, tidak dipakai lagi)
tests/Feature/Catalog/CatalogPageTest.php, ServiceDetailTest.php, CatalogDatabaseTest.php
tests/Unit/RupiahTest.php
```

### Command yang dijalankan

```powershell
git switch -c hari-2-katalog
php artisan migrate
php artisan db:seed
php artisan db:seed --class=CatalogDemoSeeder   # password demo -> C:\Users\admin\creativehub-demo-accounts.txt
vendor/bin/pint
npm run build
php artisan test
```

### Hasil testing

`php artisan test` (5 November 2026): **71 passed (189 assertions)**. Isinya 46 test Hari 1 ditambah 25 test baru:

- `CatalogPageTest` (12): homepage, empty state, harga mulai, hanya published, kategori nonaktif tersembunyi, layanan tanpa paket tersembunyi, pencarian case-insensitive, filter kategori, paginasi, halaman kategori, 404 kategori.
- `ServiceDetailTest` (5): detail dan paket berurutan dengan harga Rupiah, hanya paket aktif, 404 untuk draft/pending/rejected, 404 untuk kategori nonaktif, 404 untuk slug tidak dikenal.
- `CatalogDatabaseTest` (7): seeder kategori idempoten, demo seeder idempoten dan draf tersembunyi, slug unik, satu paket per tier, harga > 0, CHECK status, status tidak mass-assignable.
- `RupiahTest` (1).

Pemeriksaan lain:

- `vendor/bin/pint --test` passed; `npm run build` sukses.
- HTTP:
  - `/`, `/services`, `/services?q=poster`, `/services?kategori=roblox-map`, `/categories/website-development`, `/services/desain-map-obby-roblox-custom` → 200.
  - Layanan draf `/services/desain-logo-minimalis` → 404; `/categories/tidak-ada` → 404.
- Browser (Playwright):
  - Homepage dan detail layanan dicek di 1366px; tab paket Premium berfungsi; tidak ada error console.
  - Katalog mobile 375px tanpa horizontal overflow (`scrollWidth` = 375).

### Error dan solusinya

| Error / masalah | Solusi |
|---|---|
| `SvgNotFound: Svg by name "sparkles"`: komponen `<x-icon>` bentrok dengan komponen milik `blade-ui-kit/blade-icons` (dependensi Filament) | Komponen ikon diganti nama menjadi `<x-lucide>`. |
| Kelas Tailwind dinamis `grid-cols-{{ n }}` tidak akan ter-generate | Diganti `match` dengan nama kelas literal. |
| **`php artisan migrate:fresh --env=testing` me-reset database development**: tidak ada `.env.testing`, sehingga Laravel memakai `.env` | Yang hilang hanya data seed beberapa menit sebelumnya (database berisi 0 user sebelum seeding). Kategori dan katalog demo di-seed ulang; password akun demo dibuat ulang. **Jangan pakai `--env=testing`.** Test selalu memakai `creativehub_test` lewat `phpunit.xml`. |

### Pekerjaan yang belum selesai / catatan

- Commit Hari 2 dibuat dan di-merge ke `master` setelah persetujuan.
- Akun demo: email `raka@`, `nadia@`, `dimas@demo.creativehub.test`; password ada di `C:\Users\admin\creativehub-demo-accounts.txt`.
- Halaman profil publik freelancer `/freelancers/{id}` dan bagian "freelancer unggulan" di homepage belum ada (dijadwalkan bersama profil dan portofolio).
- Rating belum ditampilkan karena belum ada ulasan; tidak memakai rating palsu.
- Upload thumbnail belum ada; sampul memakai gradien per kategori.
- Pesan validasi untuk parameter pencarian tidak valid (lebih dari 100 karakter) dikembalikan sebagai redirect validasi standar.

### Rencana Hari 3 — Profil freelancer & portofolio

1. Form edit profil freelancer (headline, bio, skills, availability, portfolio URL) dengan `FreelancerProfilePolicy::update`.
2. Tabel `portfolio_items` dan upload gambar portofolio (validasi tipe, ukuran, nama file; disk `public`, `storage:link`).
3. Halaman publik `/freelancers/{id}`: profil, skills, portofolio, layanan published milik freelancer.
4. Link ke profil freelancer dari kartu dan detail layanan; bagian "Freelancer unggulan" di homepage dari data nyata.
5. Feature test: hanya pemilik yang bisa mengubah profil dan portofolio, validasi upload, dan halaman publik tidak menampilkan layanan draf.

---

## Hari 3 — Profil Freelancer & Portofolio

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-3-profil-portofolio`, di-merge ke `master`
**Status:** Selesai dan disetujui (5 November 2026)

### Fitur yang dikerjakan

- **Database:** tabel `portfolio_items`:
  - FK `user_id` cascade, `category_id` nullable dengan `nullOnDelete`.
  - Kolom `title`, `description`, `image_path`, `project_url`.
  - Index `(user_id, created_at)`.
- **Edit profil freelancer** (`/dashboard/freelancer-profile`, middleware `role:freelancer` + `FreelancerProfilePolicy::update`):
  - Headline, bio, status ketersediaan (enum), tautan portofolio eksternal (hanya `http/https`).
  - Keahlian diisi sebagai teks dipisah koma, lalu dirapikan, dibuat unik (tanpa membedakan huruf besar/kecil), maksimal 10.
  - Kolom `user_id` tidak bisa diubah dari form.
- **Portofolio** (`/dashboard/portfolio`, CRUD tanpa halaman show):
  - Upload JPG/PNG/WebP maksimal 2 MB dan 5000×5000 px; **SVG ditolak** karena bisa memuat script.
  - Nama file dibuat acak oleh server di `storage/app/public/portfolio/{user_id}/`; nama asli dari browser tidak dipakai.
  - Maksimal 12 item per freelancer, dicek di dalam transaksi dengan row lock.
  - Mengganti gambar akan menghapus file lama; menghapus item ikut menghapus filenya.
  - `PortfolioItemPolicy`: hanya pemilik yang bisa edit/hapus. `user_id` dan `image_path` tidak fillable.
  - Kategori opsional, hanya kategori aktif.
- **Profil publik** `/freelancers/{id}`:
  - Menampilkan nama, headline, status ketersediaan, tanggal bergabung, bio, keahlian, layanan published, portofolio, dan empty state.
  - Email tidak ditampilkan.
  - 404 untuk user yang bukan freelancer (customer/admin), ID tidak dikenal, atau ID bukan angka.
  - Tautan eksternal memakai `rel="noopener noreferrer nofollow"`.
- **Integrasi:**
  - Nama freelancer di detail layanan menautkan ke profil publik.
  - Bagian **Freelancer unggulan** di homepage: 4 freelancer dengan layanan aktif terbanyak (data nyata, tanpa rating palsu).
  - Navigasi dashboard menampilkan "Profil Freelancer" dan "Portofolio" untuk freelancer; halaman Profil menautkan ke pengelolaan profil freelancer.
  - Komponen `<x-avatar>` untuk avatar inisial.
- **Data demo:** `CatalogDemoSeeder` menambahkan 2 portofolio per freelancer. Gambar placeholder PNG dibuat dengan GD, tanpa unduhan internet. Seeder tetap idempoten.
- **Setup:** `php artisan storage:link`. `public/storage` sudah ada di `.gitignore`, dan file upload ada di `storage/app/public` yang di-ignore.

### File yang dibuat atau diubah

```text
database/migrations/2026_11_05_000006_create_portfolio_items_table.php
database/factories/PortfolioItemFactory.php
database/seeders/CatalogDemoSeeder.php       (portofolio demo + gambar placeholder GD)
app/Models/PortfolioItem.php
app/Models/User.php                          (portfolioItems, scope publicFreelancers)
app/Policies/PortfolioItemPolicy.php
app/Http/Requests/FreelancerProfileUpdateRequest.php
app/Http/Requests/PortfolioItemRequest.php
app/Http/Controllers/FreelancerProfileController.php   (edit, update, show publik)
app/Http/Controllers/PortfolioController.php
app/Http/Controllers/HomeController.php      (freelancer unggulan)
app/Http/Controllers/ServiceController.php   (eager load roles freelancer)
routes/web.php
resources/views/freelancer/profile-edit.blade.php
resources/views/freelancer/portfolio/index.blade.php, edit.blade.php, _form.blade.php
resources/views/freelancers/show.blade.php
resources/views/components/avatar.blade.php
resources/views/home.blade.php, services/show.blade.php
resources/views/layouts/navigation.blade.php, profile/partials/account-roles.blade.php
tests/Feature/Freelancer/FreelancerProfileEditTest.php
tests/Feature/Freelancer/PortfolioManagementTest.php
tests/Feature/Freelancer/FreelancerPublicProfileTest.php
tests/Feature/Catalog/CatalogDatabaseTest.php   (assert portofolio demo)
```

### Command yang dijalankan

```powershell
git switch -c hari-3-profil-portofolio
php artisan migrate                      # dicek dulu: DB aktif = creativehub
php artisan storage:link
php artisan db:seed --class=CatalogDemoSeeder   # tidak ada akun baru, password demo tidak berubah
vendor/bin/pint
npm run build
php artisan test
```

### Hasil testing

`php artisan test` (5 November 2026): **92 passed (284 assertions)**. Isinya 71 test sebelumnya ditambah 21 test baru:

- `FreelancerProfileEditTest` (6): guest diarahkan ke login, customer 403, form tampil, update berhasil (normalisasi skills, enum, URL), validasi (headline, availability, URL `javascript:`, lebih dari 10 skills), `user_id` tidak bisa diubah.
- `PortfolioManagementTest` (10): customer 403, halaman portofolio tampil, upload berhasil ke `portfolio/{id}/` dengan nama acak, tolak PDF/SVG/lebih dari 2 MB/tanpa gambar, tolak kategori nonaktif dan URL tidak aman, batas 12 item, ganti gambar menghapus file lama, update tanpa gambar mempertahankan gambar lama, hapus item ikut menghapus file, freelancer lain 403 untuk edit/update/hapus.
- `FreelancerPublicProfileTest` (5): profil publik (layanan draf dan email tidak tampil), empty state, 404 untuk customer/admin/ID tidak dikenal/bukan angka, link dari detail layanan, freelancer unggulan di homepage.

Pemeriksaan lain:

- `vendor/bin/pint` passed; `npm run build` sukses.
- HTTP:
  - `/freelancers/1` → 200; `/freelancers/999` → 404.
  - `/dashboard/portfolio` dan `/dashboard/freelancer-profile` untuk guest → 302 ke `/login`.
  - Gambar `/storage/portfolio/...png` → 200 `image/png`.
- Browser (Playwright, 1366px): profil publik freelancer tampil dengan layanan, portofolio, dan keahlian. Halaman dashboard freelancer belum dicek visual di browser (perlu login akun demo); perilakunya tercakup feature test.

### Error dan solusinya

Tidak ada error berarti. Sebelum `migrate`, nama database aktif diperiksa terlebih dahulu (pelajaran dari Hari 2).

### Pekerjaan yang belum selesai / catatan

- Commit Hari 3 dibuat dan di-merge ke `master` setelah persetujuan.
- Foto profil (avatar upload) belum ada; memakai avatar inisial.
- Moderasi portofolio oleh admin belum ada (dijadwalkan bersama moderasi admin).
- Belum ada pengecekan isi gambar selain MIME, ekstensi, dan dimensi. Untuk MVP lokal ini dianggap cukup.

### Rencana Hari 4 — Layanan dan paket oleh freelancer

1. Dashboard freelancer `/dashboard/services`: daftar layanan milik sendiri beserta statusnya.
2. Buat dan edit layanan (judul, kategori aktif, deskripsi, thumbnail opsional dengan aturan upload yang sama) dan paket Basic/Standard/Premium (harga Rupiah integer, waktu pengerjaan, revisi, aktif/nonaktif).
3. Slug unik dibuat server; status awal `draft`. Freelancer dapat mengajukan review (`pending_review`). Publikasi tetap lewat moderasi admin (Hari 5).
4. `ServicePolicy`: hanya pemilik yang bisa mengubah. Layanan yang sudah published dan diedit kembali ke `pending_review`. Minimal satu paket aktif sebelum diajukan.
5. Feature test: kepemilikan, validasi harga (integer > 0, batas maksimum), slug unik, draf tidak tampil publik, status tidak bisa dipalsukan dari form.

---

## Hari 4 — Layanan dan Paket oleh Freelancer

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-4-layanan-freelancer`, di-merge ke `master`
**Status:** Selesai dan disetujui (5 November 2026)

### Fitur yang dikerjakan

- **Dashboard `/dashboard/services`** (middleware `role:freelancer` + `ServicePolicy`):
  - Daftar layanan milik sendiri: status, kategori, jumlah paket aktif, harga mulai, tanggal diperbarui.
  - Aksi Lihat, Edit, Ajukan Review, Tarik, dan Hapus muncul sesuai status layanan.
- **Tambah/edit layanan dan paket dalam satu form:**
  - Judul 10–150 karakter, kategori aktif, deskripsi 30–5000 karakter, thumbnail opsional (JPG/PNG/WebP ≤ 2 MB, SVG ditolak, nama file acak di `services/{user_id}/`).
  - Paket Basic/Standard/Premium bisa diaktifkan per tier (Alpine). Minimal satu paket aktif.
  - Field paket hanya divalidasi jika paket diaktifkan (`exclude_unless`). Tier di luar basic/standard/premium ditolak.
  - Harga integer Rp10.000–Rp100.000.000. Format "1.250.000" diterima; desimal, nol, negatif, dan nilai terlalu besar ditolak.
  - Waktu pengerjaan 1–365 hari, revisi 0–20.
- **Ditentukan server:**
  - `user_id` (dari user login).
  - `slug` unik dari judul (`judul`, `judul-2`, …), tidak berubah saat judul diedit agar URL stabil.
  - `thumbnail_path` dan `status`. Status awal selalu `draft`.
  - `slug` dan `thumbnail_path` dikeluarkan dari `$fillable` model `Service`.
- **Alur status** (method di model `Service`):
  - `submitForReview()`: draf atau ditolak → `pending_review`, wajib punya minimal satu paket aktif.
  - `withdraw()`: published atau pending → draf (`published_at` dikosongkan).
  - `markEditedByOwner()`: layanan published yang diedit → `pending_review` (dimoderasi ulang).
  - Hapus hanya untuk layanan yang tidak sedang published; layanan published harus ditarik dulu. Hapus ikut menghapus paket (cascade) dan thumbnail.
  - Publikasi (`pending_review` → `published`) belum tersedia di UI; menunggu moderasi admin di Hari 5.
- **Paket yang dimatikan** disimpan sebagai `is_active = false`, tidak dihapus, untuk keperluan riwayat pesanan nanti.
- **UI:** komponen `<x-service-status-badge>`, menu "Layanan Saya" di navigasi freelancer, peringatan saat mengedit layanan yang sedang tayang.

### File yang dibuat atau diubah

```text
app/Models/Service.php                     (fillable diperketat, uniqueSlug, submitForReview, withdraw, markEditedByOwner, isDeletable)
app/Policies/ServicePolicy.php
app/Http/Requests/ServiceRequest.php
app/Http/Controllers/FreelancerServiceController.php
routes/web.php                             (resource dashboard/services + submit + withdraw)
database/seeders/CatalogDemoSeeder.php     (slug di-set eksplisit karena tidak fillable lagi)
resources/views/freelancer/services/index.blade.php, create.blade.php, edit.blade.php, _form.blade.php
resources/views/components/service-status-badge.blade.php
resources/views/layouts/navigation.blade.php
tests/Feature/Freelancer/ServiceManagementTest.php
```

### Command yang dijalankan

```powershell
git switch -c hari-4-layanan-freelancer
php artisan test
vendor/bin/pint
npm run build
```

Tidak ada migration baru di Hari 4.

### Hasil testing

`php artisan test` (5 November 2026): **110 passed (382 assertions)**. Isinya 92 test sebelumnya ditambah 18 test di `ServiceManagementTest`:

- guest/customer ditolak;
- daftar hanya menampilkan layanan sendiri;
- form tampil;
- buat draf: input `status`/`user_id`/`slug` palsu diabaikan, thumbnail tersimpan, draf memberi 404 di publik;
- slug unik `-2`/`-3`;
- harga "1.250.000" diterima;
- validasi (judul, kategori nonaktif, SVG, harga 0/negatif/desimal/terlalu besar, hari 0, tanpa paket aktif, tier tidak dikenal);
- update layanan dan paket (slug tetap, paket dimatikan menjadi nonaktif);
- edit layanan published kembali ke review;
- ganti thumbnail menghapus file lama;
- freelancer lain mendapat 403 untuk edit/update/submit/withdraw/delete;
- ajukan review dari draf dan dari ditolak;
- tolak pengajuan tanpa paket aktif dan pengajuan ulang;
- tarik layanan published;
- hapus draf beserta paket dan thumbnail;
- tolak hapus layanan published.

Pemeriksaan lain:

- `vendor/bin/pint` passed; `npm run build` sukses.
- **Uji browser end-to-end (Playwright)** memakai akun freelancer **sementara** yang dibuat dan dihapus di database dev:
  - login, buka form tambah layanan, isi, simpan sebagai draf, ajukan review;
  - hasilnya status "Menunggu Review", harga "mulai Rp85.000", tanpa error console.
  - Setelah dihapus, cascade membersihkan layanan dan paket uji; database dev kembali berisi 3 user dan 7 layanan.

### Error dan solusinya

Tidak ada error berarti.

### Pekerjaan yang belum selesai / catatan

- Commit Hari 4 dibuat dan di-merge ke `master`.
- Layanan baru belum bisa tayang sampai moderasi admin dibuat (Hari 5). Layanan demo dari seeder tetap published.
- Belum ada catatan alasan penolakan; akan ditambahkan bersama moderasi.
- Tombol file input memakai teks bawaan browser ("Choose file").

### Rencana Hari 5 — Moderasi admin (Filament) dan pencarian

1. Filament Resource **Layanan**: daftar dengan filter status/kategori; aksi **Setujui** (`pending_review` → `published`, isi `published_at`) dan **Tolak** dengan alasan wajib (kolom baru `rejection_reason`), yang ditampilkan ke freelancer.
2. Filament Resource **Kategori**: CRUD, aktif/nonaktif, urutan.
3. Tabel `audit_logs` dan pencatatan aksi moderasi admin (siapa, apa, kapan, data sebelum/sesudah).
4. Widget dashboard admin sederhana: jumlah layanan menunggu review, total freelancer, total layanan published.
5. Filter katalog tambahan: rentang harga (min/maks) dan urutan (terbaru, harga terendah/tertinggi).
6. Feature test: hanya admin yang bisa moderasi, transisi status valid, alasan penolakan wajib, audit log tercatat, dan filter harga benar.
