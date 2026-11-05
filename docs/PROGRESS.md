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

---

## Hari 5 — Moderasi Admin, Kategori, Audit Log, Filter Harga

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-5-moderasi-admin`, di-merge ke `master`
**Status:** Selesai (mode lanjut terus sesuai instruksi; commit dan merge per hari)

### Fitur yang dikerjakan

- **Migration:**
  - `services`: kolom `rejection_reason`, `moderated_by` (FK users, `nullOnDelete`), `moderated_at`.
  - Tabel `audit_logs` (append-only): pelaku, aksi, objek polymorphic, `old_values`/`new_values` jsonb, IP, waktu.
- **Moderasi** (model `Service::approve()` / `reject()`, hanya dari `pending_review`):
  - Setujui mengubah status menjadi `published`, mengisi `published_at`, dan mencatat moderator.
  - Tolak wajib alasan (minimal 10 karakter di form). Alasan ditampilkan di dashboard freelancer.
  - Pengajuan ulang menghapus alasan penolakan lama.
  - Setiap moderasi dicatat ke audit log dalam transaksi yang sama.
- **Filament (panel `/admin`, semua via policy):**
  - **Layanan**: tabel (judul, freelancer, kategori, badge status, harga mulai), filter status/kategori, halaman detail berisi paket, aksi **Setujui**/**Tolak** (`ServicePolicy::moderate`: admin + status pending). Admin tidak bisa membuat atau mengedit konten freelancer. Menu menampilkan badge jumlah layanan pending.
  - **Kategori**: CRUD, slug otomatis dari nama saat dibuat, slug unik dan alpha-dash, pilihan ikon, urutan, aktif/nonaktif. Kategori yang sudah dipakai layanan tidak bisa dihapus (`CategoryPolicy::delete`). Bulk delete dimatikan. Perubahan dicatat ke audit log lewat `CategoryObserver`; perubahan dari seeder/console tidak dicatat.
  - **Audit Log**: hanya baca (`AuditLogPolicy`), filter aksi, ringkasan nilai sebelum/sesudah.
  - **Dasbor**: widget statistik (layanan menunggu review dengan tautan filter, layanan published, jumlah freelancer, jumlah customer). Widget info Filament dihapus. Grup navigasi: Katalog, Transaksi, Pengguna, Sistem.
- **Filter katalog `/services`:**
  - Harga mulai `harga_min`/`harga_maks`, dihitung dari paket aktif termurah (subquery).
  - Urutan `urut=terbaru|termurah|termahal`.
  - Nilai tidak valid diabaikan, bukan menghasilkan error.
  - Filter dipertahankan saat berpindah kategori, dan ada tombol Reset.
- `ServiceStatus` mengimplementasikan `HasLabel`/`HasColor` milik Filament untuk badge.

### Hasil testing

`php artisan test`: **131 passed (493 assertions)**. Isinya 110 test sebelumnya ditambah 21 test baru:

- `ServiceModerationTest` (9): admin melihat daftar dan detail; non-admin 403; setujui (status, moderator, tampil publik, audit log); tolak dengan alasan tampil ke freelancer; alasan wajib; aksi tersembunyi untuk status non-pending; transisi tidak valid ditolak model; pengajuan ulang menghapus alasan; widget dasbor.
- `CategoryAdminTest` (7): buat kategori dengan audit log; slug unik dan valid; nonaktifkan kategori dengan audit dan 404 publik; kategori terpakai tidak bisa dihapus; non-admin 403 untuk kategori dan audit log; admin membaca audit log; seeder tidak tercatat.
- `CatalogFilterTest` (5): rentang harga, paket nonaktif diabaikan, urutan harga, nilai tidak valid diabaikan, filter dipertahankan di tautan.

Pemeriksaan lain:

- Pint passed; build sukses.
- `migrate` di database dev (dicek dulu: `creativehub`) menjalankan 2 migration baru.
- **Browser:** login dengan akun admin **sementara**. Dasbor `/admin` tampil dalam Bahasa Indonesia, badge "1" di menu Layanan, widget terisi. Setelah itu akun dihapus dan layanan demo yang sempat diajukan dikembalikan ke draf.

### Error dan solusinya

| Masalah | Solusi |
|---|---|
| Test dasbor gagal karena widget Filament dimuat lazy (isi belum ada di HTML awal) | `$isLazy = false` pada `MarketplaceStats` |
| Grup navigasi "Sistem" tampil di atas "Katalog" | `->navigationGroups([...])` di `AdminPanelProvider` |

### Catatan

- Akun admin untuk demo dibuat sendiri oleh pengguna dengan `php artisan app:create-admin`.

### Rencana Hari 6 — Proyek customer

1. Tabel `projects` (customer, kategori, judul, deskripsi, anggaran min/maks opsional dalam integer Rupiah, tenggat, status `open|in_progress|completed|cancelled|closed`).
2. Customer membuat, mengedit, dan menutup proyek miliknya (`/dashboard/projects`). Edit hanya saat status open.
3. Halaman publik `/projects` (proyek open, filter kategori) dan `/projects/{id}`.
4. `ProjectPolicy`, feature test kepemilikan, validasi anggaran dan tenggat, visibilitas publik.

---

## Hari 6 — Proyek Customer

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-6-proyek-customer`, di-merge ke `master`
**Status:** Selesai

### Fitur yang dikerjakan

- **Tabel `projects`:**
  - `customer_id` (cascade), `category_id` (restrict), judul, deskripsi.
  - `budget_min`/`budget_max` integer Rupiah opsional dengan CHECK `min ≤ max`.
  - `deadline`, status `open|in_progress|completed|closed` dengan CHECK, `closed_at`.
  - Index `(status, category_id)` dan `customer_id`.
- **Model `Project`:**
  - `customer_id`, `status`, `closed_at` tidak fillable.
  - Scope `publiclyVisible()`: status open dan kategori aktif.
  - `close()` hanya untuk proyek open.
  - `budgetLabel()`: rentang, mulai, hingga, atau "Anggaran fleksibel".
- **Dashboard customer `/dashboard/projects`** (middleware `role:customer` + `ProjectPolicy`):
  - Daftar milik sendiri, posting, detail, edit (hanya saat open), tutup proyek.
  - Proyek langsung tampil publik saat dibuat; tidak ada moderasi khusus proyek di MVP, hanya validasi.
- **Validasi `ProjectRequest`:**
  - Judul 10–150, kategori aktif, deskripsi 30–5000.
  - Anggaran opsional Rp10.000–Rp1.000.000.000 dengan format "500.000" diterima; maksimum ≥ minimum.
  - Tenggat opsional, setelah hari ini dan maksimal 1 tahun.
- **Publik:**
  - `/projects`: proyek open di kategori aktif, pencarian judul, filter kategori, paginasi, empty state.
  - `/projects/{id}`: hanya proyek open (lainnya 404), tanpa email customer.
  - Kartu penawaran berisi placeholder sampai Hari 7.
- **Navigasi:** "Proyek" di header dan footer publik, "Proyek Saya" untuk customer di dashboard.
- **Refactor:**
  - `Rupiah::normalizeInput()` dipakai bersama oleh `ServiceRequest` dan `ProjectRequest`.
  - Komponen generik `<x-status-badge>` untuk enum yang memiliki `getLabel()`/`getColor()`.

### Hasil testing

`php artisan test`: **144 passed (560 assertions)**. Isinya 131 test sebelumnya ditambah 13 test baru:

- `ProjectManagementTest` (8): guest/admin-only ditolak; buat proyek (input `status`/`customer_id` palsu diabaikan, anggaran "500.000" diterima); validasi judul/kategori/deskripsi/anggaran/tenggat; anggaran dan tenggat opsional; hanya proyek sendiri; edit hanya saat open; customer lain 403; tutup sekali.
- `PublicProjectTest` (5): hanya proyek open di kategori aktif; pencarian dan filter; detail publik hanya saat open dan tanpa email; empty state; CHECK anggaran terbalik.

Pemeriksaan lain:

- Pint dan build OK.
- `migrate` dev (DB `creativehub`) menjalankan 1 migration.
- `/projects` → 200, `/dashboard/projects` (guest) → 302.

### Catatan

- Data demo proyek belum ada; akan ditambahkan pada seeder demo lengkap (tahap UAT).

### Rencana Hari 7 — Proposal freelancer dan penerimaan proposal

1. Tabel `proposals` (proyek, freelancer, harga integer, estimasi hari, pesan, status `pending|accepted|rejected|withdrawn`); unique aktif per freelancer per proyek.
2. Freelancer mengirim, mengubah, dan menarik proposal pada proyek open milik orang lain.
3. Customer melihat proposal di detail proyeknya dan menerima **satu** proposal (transaksi + `lockForUpdate`). Proposal lain otomatis ditolak dan proyek menjadi `in_progress`.
4. Tabel `orders` (sumber tepat satu: paket **atau** proposal, snapshot judul/harga/scope, status `pending_payment`…), dan `order_status_histories`. Penerimaan proposal membuat order `pending_payment`.
5. Feature test aturan bisnis: tidak bisa menawar proyek sendiri, satu proposal aktif, hanya proyek open, hanya satu diterima (termasuk percobaan ganda), snapshot order benar.

---

## Hari 7 — Proposal Freelancer, Penerimaan Proposal, dan Fondasi Order

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-7-proposal-order`, di-merge ke `master`
**Status:** Selesai

### Fitur yang dikerjakan

- **Tabel `proposals`:**
  - Harga integer (CHECK > 0), estimasi hari (CHECK > 0), revisi, pesan.
  - Status `pending|accepted|rejected|withdrawn` dengan CHECK.
  - Unique index parsial `(project_id, freelancer_id) WHERE status IN (pending, accepted)`: satu proposal aktif per freelancer per proyek.
  - Unique index parsial `(project_id) WHERE status = accepted`: **maksimal satu proposal diterima per proyek**, dijamin database.
- **Tabel `orders`:**
  - Nomor order unik `ORD-yymmdd-XXXXXX`.
  - FK customer dan freelancer `restrictOnDelete`.
  - Sumber order: CHECK **tepat satu**, yaitu (layanan + paket) **atau** (proyek + proposal). `proposal_id` unik.
  - Snapshot: judul, tier/nama paket, scope, hari, revisi.
  - Nominal integer: subtotal, total, `commission_rate_bps`, komisi, bagian freelancer. CHECK `total = subtotal = komisi + bagian freelancer`, `total > 0`, customer ≠ freelancer, `revisi terpakai ≤ jatah`.
  - Status 7 tahap dengan CHECK.
- **Tabel `order_status_histories`:** dari, ke, oleh, catatan, waktu.
- **Komisi:**
  - `config/creativehub.php` dengan `CREATIVEHUB_COMMISSION_BPS=1000` (10%, ada di `.env.example`).
  - `CommissionCalculator::split()`: integer, komisi dibulatkan ke bawah sehingga komisi + bagian freelancer selalu sama dengan total. Persentase di-snapshot ke order.
- **Transisi status order:**
  - `OrderStatus::allowedNext()` mendefinisikan alur pending_payment → paid → in_progress → delivered ⇄ revision_requested → completed, serta pending_payment → cancelled.
  - `Order::transitionTo()` menolak transisi tidak valid dan mencatat riwayat.
- **Freelancer:**
  - Kirim penawaran dari halaman proyek publik (`ProjectPolicy::propose`: punya role freelancer, bukan proyek sendiri, proyek open, belum punya penawaran aktif).
  - Halaman "Penawaran Saya" berisi daftar, edit, dan tarik penawaran selama masih menunggu dan proyek masih open.
  - Bisa menawar ulang setelah menarik.
  - Kiriman ganda bersamaan ditahan unique index, lalu dikonversi menjadi pesan validasi.
- **Customer:**
  - Detail proyek menampilkan semua penawaran (profil freelancer, harga, hari, revisi, pesan) dengan tombol **Terima**/**Tolak** (`ProposalPolicy::decide`).
  - `OrderPlacement::acceptProposal()` berjalan dalam **satu transaksi dengan `lockForUpdate` pada proyek dan proposal**, lalu memeriksa ulang kepemilikan dan status:
    - proposal menjadi accepted;
    - proposal pending lain menjadi rejected;
    - proyek menjadi in_progress;
    - order `pending_payment` dibuat dengan snapshot dan komisi, dan riwayat awal dicatat.
- **Pesanan** (`/dashboard/orders`, `OrderPolicy::view`: customer, freelancer terkait, atau admin):
  - Daftar dengan tab "Sebagai customer" dan "Sebagai freelancer".
  - Detail berisi scope (snapshot), riwayat status, dan rincian. Freelancer melihat nilai, komisi, dan bagiannya; customer melihat total. Ada info menunggu pembayaran.
- **Keamanan akun:** user yang punya riwayat pesanan tidak bisa menghapus akun (pesan validasi, bukan error 500 dari FK).
- **Navigasi dashboard** dirapikan: Dashboard, Pesanan, Proyek Saya, dropdown **Freelancer** (Layanan, Penawaran, Portofolio, Profil, Cari Proyek), Katalog Jasa, Panel Admin.

### Hasil testing

`php artisan test`: **173 passed (728 assertions)**. Isinya 144 test sebelumnya ditambah 29 test baru:

- `ProposalTest` (10): kirim penawaran (input `status`/`freelancer_id` palsu diabaikan); tidak bisa menawar proyek sendiri; satu penawaran aktif; database menolak duplikat; menawar ulang setelah tarik; proyek closed/in_progress/completed ditolak; customer tanpa role freelancer ditolak; validasi; edit hanya milik sendiri dan selama pending; daftar milik sendiri.
- `ProposalAcceptanceTest` (8): customer melihat penawaran; penerimaan membuat order dengan snapshot, komisi 10% (Rp1.234.567 → komisi Rp123.456, freelancer Rp1.111.111), riwayat, dan snapshot tetap walau proposal diubah; hanya satu bisa diterima; **service memeriksa ulang di dalam lock** untuk percobaan ganda; database menolak dua accepted; pihak lain 403; tolak satu penawaran; proyek tertutup tidak bisa menerima.
- `OrderAccessTest` (7): akses detail hanya pihak terkait dan admin; guest diarahkan; daftar dipisah per peran; transisi status dipaksakan dan dicatat; user dengan order tidak bisa hapus akun; CHECK nominal; CHECK sumber order.
- `CommissionCalculatorTest` (4, unit): pembagian, pembulatan dan jumlah selalu sama dengan total, validasi input.

Pemeriksaan lain: Pint dan build OK; `migrate` dev (DB `creativehub`) menjalankan 2 migration.

### Error dan solusinya

| Masalah | Solusi |
|---|---|
| Test mengubah status proposal lewat `update()` tidak berefek karena `status` memang tidak fillable | Test memakai `forceFill()`; perilaku aplikasi sudah benar |
| Closure `OrderFactory` gagal saat `proposal_id` null | Test CHECK sumber order memberi atribut eksplisit |
| Hapus akun user yang punya order akan memicu error FK | Dicegah di `ProfileController::destroy` dengan pesan validasi |

### Rencana Hari 8 — Checkout paket layanan

1. Tombol "Pesan Paket" di detail layanan untuk user login (bukan pemilik layanan) dan halaman konfirmasi `/checkout/package/{package}` yang menampilkan ringkasan dari database.
2. `OrderPlacement::orderPackage()`: hanya layanan published dengan kategori dan paket aktif, customer ≠ freelancer; snapshot judul, tier, scope, hari, revisi, dan harga; komisi dihitung server; harga dari browser diabaikan.
3. Layanan atau paket yang sudah punya order tidak bisa dihapus (tetap bisa dinonaktifkan atau ditarik).
4. Feature test: membeli layanan sendiri ditolak, layanan draft/paket nonaktif ditolak, manipulasi harga diabaikan, snapshot tidak berubah saat paket diedit.

---

## Hari 8 — Checkout Paket Layanan

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-8-checkout-paket`, di-merge ke `master`
**Status:** Selesai

### Fitur yang dikerjakan

- **Detail layanan:** tombol **Pesan Paket {tier}** aktif dan menuju `/checkout/package/{id}`. Guest diarahkan ke login lebih dulu; pemilik layanan melihat label "Ini layanan Anda". Ada keterangan mode demo.
- **Halaman konfirmasi** (`CheckoutController@create`): ringkasan layanan, paket, dan biaya diambil dari database. 404 untuk layanan yang tidak publik atau paket nonaktif.
- **Form checkout:** catatan opsional untuk freelancer (maksimal 2000 karakter) dan persetujuan wajib, termasuk pernyataan bahwa pembayaran hanya simulasi.
- **`OrderPlacement::orderPackage()`** dalam transaksi dengan `lockForUpdate` paket:
  - memastikan layanan publik dan paket aktif, customer ≠ pemilik, akun memiliki role customer;
  - snapshot judul, tier, nama paket, scope (+ catatan customer), hari, revisi, harga;
  - komisi dihitung server; field harga/total/freelancer dari browser diabaikan;
  - riwayat awal `pending_payment` dicatat.
- **Klik ganda:** order `pending_payment` yang sudah ada untuk customer dan paket yang sama dipakai ulang, sehingga tidak ada order duplikat.
- **Perlindungan data:** layanan yang sudah pernah dipesan tidak bisa dihapus freelancer (`Service::isDeletable()`), tetapi tetap bisa ditarik atau paketnya dinonaktifkan. FK order ke paket bersifat `restrict`.

### Hasil testing

`php artisan test`: **184 passed (785 assertions)**. Isinya 173 test sebelumnya ditambah 11 test di `CheckoutPackageTest`:

- guest diarahkan ke login;
- tautan checkout ada di detail layanan;
- konfirmasi menampilkan data dari database;
- order dibuat dengan harga Rp300.000 dari database walau browser mengirim `price=1`/`total_amount=1`, komisi Rp30.000 dan bagian freelancer Rp270.000;
- snapshot tidak berubah saat paket/layanan diedit;
- klik ganda tidak membuat order ganda;
- persetujuan wajib;
- tidak bisa membeli layanan sendiri;
- layanan draft, kategori nonaktif, dan paket nonaktif ditolak (404 dan error);
- akun admin-only tidak bisa memesan;
- layanan yang sudah dipesan tidak bisa dihapus.

### Error dan solusinya

| Masalah | Solusi |
|---|---|
| Setup test membuat paket Premium kedua sehingga melanggar unique `(service_id, tier)` | Test menonaktifkan paket Premium yang sudah ada |

### Rencana Hari 9 — Pembayaran simulasi (dummy payment gateway)

1. Tabel `payments` (kode transaksi unik, order, metode, status `pending|success|failed|expired|cancelled`, nominal snapshot, `paid_at`, referensi provider) dan `payment_events` (log setiap kejadian).
2. Interface `PaymentGateway` + `DummyPaymentGateway` (QRIS dummy, Virtual Account dummy, e-wallet dummy). Tidak ada kartu, PIN, atau OTP.
3. Halaman checkout pembayaran `/checkout/{order}`: pilih metode, lalu instruksi dummy (kode QR teks, nomor VA, nomor e-wallet), dengan banner **"Mode Demo — pembayaran tidak menggunakan uang sungguhan"**.
4. Simulasi hasil **berhasil, pending, gagal** diproses di server (POST + CSRF + otorisasi + idempotensi + `lockForUpdate`). URL "sukses" saja tidak mengubah status.
5. Pembayaran berhasil: order menjadi `paid` lalu `in_progress`, dengan riwayat status. Transaksi tidak boleh diproses dua kali.
6. Feature test: hanya customer pemilik yang bisa membayar, nominal dari server, idempotensi, pembayaran gagal tidak mengubah order, redirect sukses palsu tidak berpengaruh.

---

## Hari 9 — Pembayaran Simulasi (Dummy Payment Gateway)

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-9-pembayaran-dummy`, di-merge ke `master`
**Status:** Selesai

### Fitur yang dikerjakan

- **Tabel `payments`:**
  - `transaction_code` unik `TRX-yyyymmdd-XXXXXXXX`, order (restrict), customer, metode, provider (`dummy`), referensi (QR/VA/e-wallet dummy).
  - Snapshot nominal integer: amount, komisi, bagian freelancer.
  - Status `pending|success|failed|expired|cancelled`; `expires_at` (24 jam), `paid_at`, `failed_at`.
  - CHECK metode, status, `amount = komisi + freelancer`, `success ⇒ paid_at`.
  - **Unique parsial: satu `success` per order dan satu `pending` per order.**
- **Tabel `payment_events`** (append-only): event, status dari/ke, user, payload, IP, waktu.
- **Arsitektur adapter:**
  - Interface `App\Payments\PaymentGateway` (`name`, `createCharge`, `supportsSimulation`) dengan implementasi `DummyPaymentGateway`.
  - Binding di `AppServiceProvider`, sehingga provider sungguhan bisa ditambahkan dengan mengganti binding.
  - Tidak ada klaim terhubung ke provider asli.
- **`PaymentProcessor`:**
  - `start()`: kunci order, pastikan customer pemilik dan status `pending_payment`, batalkan tagihan menunggu lama (ganti metode), buat tagihan dengan nominal **dari order**, log `created`.
  - `applyResult()` (pada integrasi sungguhan dipanggil oleh handler notifikasi yang terverifikasi; pada demo dipanggil tombol simulasi):
    - kunci payment dan order;
    - **idempoten**: hasil yang sama untuk transaksi final dicatat `duplicate_ignored`, hasil berbeda ditolak;
    - tagihan kedaluwarsa disimpan sebagai `expired` lalu ditolak;
    - **berhasil**: cek nominal sama dengan total order, ubah payment menjadi success, order `paid` lalu `in_progress` dengan riwayat;
    - **gagal**: payment failed, order tetap menunggu, bisa bayar ulang;
    - **pending**: hanya dicatat.
- **UI:**
  - `/checkout/{order}` (customer pemilik dan order pending): pilih QRIS, Virtual Account, atau E-Wallet (semuanya "Simulasi").
  - `/payments/{id}`: instruksi dummy (QR ilustrasi yang tidak bisa dipindai, nomor VA dummy, referensi e-wallet), tombol **Simulasikan Berhasil / Pending / Gagal** (POST + CSRF + throttle 30/menit), dan log transaksi.
  - Banner **"Mode Demo — pembayaran tidak menggunakan uang sungguhan"** di semua halaman pembayaran, dengan peringatan untuk tidak memasukkan kartu, PIN, OTP, atau kata sandi bank.
  - Halaman pesanan menampilkan tombol **Bayar Sekarang** dan daftar transaksi untuk customer.
- **Otorisasi (`PaymentPolicy`):** detail transaksi untuk customer pemilik dan admin; simulasi hanya oleh customer pemilik. Admin dan freelancer tidak bisa mensimulasikan. `OrderPolicy::pay` untuk memulai pembayaran.

### Hasil testing

`php artisan test`: **198 passed (890 assertions)**. Isinya 184 test sebelumnya ditambah 14 test di `DummyPaymentTest`:

- checkout menampilkan banner demo, 3 metode, dan total, serta tombol Bayar Sekarang;
- hanya customer pemilik yang bisa memulai pembayaran (freelancer, customer lain, dan admin 403);
- nominal dari server walau browser mengirim `amount=1`, referensi dan kode transaksi valid, mengganti metode membatalkan tagihan lama;
- metode tidak valid ditolak;
- simulasi berhasil: order `paid` lalu `in_progress` beserta riwayat dan event;
- **berhasil hanya diproses sekali** (duplikat dicatat, hasil berbeda ditolak, order lunas tidak bisa ditagih lagi);
- gagal: order tetap pending lalu bisa bayar ulang hingga berhasil;
- pending tidak mengubah apa pun;
- **membuka URL "sukses" (GET dengan `?status=success`) tidak mengubah status**, dan GET ke endpoint simulasi menghasilkan 405;
- hanya pemilik yang bisa melihat dan mensimulasikan, admin hanya melihat;
- hasil simulasi tidak valid ditolak;
- tagihan kedaluwarsa ditolak dan berstatus expired;
- nominal yang dimanipulasi di database ditolak;
- database menolak dua pembayaran sukses per order.

Pemeriksaan lain:

- Pint dan build OK; `migrate` dev (DB `creativehub`) menjalankan 1 migration.
- **Uji browser end-to-end** dengan customer **sementara**: login, konfirmasi paket Standard "Desain Poster Event & Promosi", Buat Pesanan, Bayar Sekarang, QRIS, Simulasikan Berhasil.
  - Hasil di database: order `in_progress` dengan `paid_at` terisi, payment `success` Rp150.000, riwayat `pending_payment, paid, in_progress`, event `created, simulated_success`.
  - Data uji dihapus setelahnya; dev kembali berisi 3 user dan 0 order.

### Error dan solusinya

| Masalah | Solusi |
|---|---|
| Status `expired` ikut ter-rollback karena exception dilempar di dalam transaksi | Flag `$expired` ditandai di dalam transaksi, exception dilempar setelah commit |
| Test riwayat status gagal karena order dari factory tidak punya riwayat awal | `setUp` mencatat riwayat `pending_payment` seperti alur aplikasi |

### Catatan

- **UX (belum diperbaiki):** setelah login, Breeze mengarahkan ke "intended URL". Jika sesi sebelumnya pernah membuka `/admin`, user non-admin bisa mendarat di halaman 403. Akan dirapikan pada hari keamanan/UX.
- Integrasi Midtrans tidak dilakukan; arsitektur adapter sudah siap jika diperlukan.

### Rencana Hari 10 — Struk pembayaran (cetak dan PDF)

1. Halaman struk `/dashboard/receipts/{payment}` untuk pembayaran berhasil: identitas CreativeHub, nomor transaksi, tanggal, customer, freelancer, layanan/paket, rincian harga, komisi, total, metode, status, dan keterangan simulasi. Semua data dari database.
2. CSS cetak yang rapi (tombol cetak browser) dan **unduh PDF** (DomPDF bila kompatibel dengan Laravel 13).
3. Otorisasi: customer pemilik dan admin; freelancer dan customer lain 403; pembayaran non-sukses 404.
4. Daftar pembayaran customer `/dashboard/payments`.
5. Feature test akses, isi struk, dan respons PDF.

---

## Hari 10 — Struk Pembayaran (Cetak dan PDF)

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-10-struk`, di-merge ke `master`
**Status:** Selesai

### Fitur yang dikerjakan

- **Paket:** `barryvdh/laravel-dompdf` v3.1.2 (dompdf 3.1.6), kompatibel dengan Laravel 13.
- **Struk** `/dashboard/receipts/{payment}`:
  - Halaman mandiri siap cetak: tombol **Cetak Struk** (`window.print()`), **Unduh PDF**, dan Kembali. Toolbar disembunyikan saat dicetak, dengan `@page A5`.
  - Isi struk: identitas CreativeHub, cap "LUNAS (SIMULASI)", nomor transaksi, nomor pesanan, tanggal bayar (WIB), metode, referensi, status, customer, freelancer, layanan/paket (snapshot), hari dan revisi, harga, biaya layanan, total, **pembagian komisi dan bagian freelancer** (ditandai pencatatan simulasi, bukan payout), keterangan simulasi, dan waktu cetak.
  - Template `receipts/_document` + `_styles` dipakai bersama oleh HTML dan PDF, dengan CSS sederhana yang aman untuk DomPDF.
- **PDF** `/dashboard/receipts/{payment}/pdf`: A5, font subsetting aktif, nama file `struk-{kode}.pdf`.
- **Otorisasi:** customer pemilik dan admin (`PaymentPolicy::view`); freelancer dan customer lain 403; pembayaran yang belum berhasil memberi 404. Semua nilai dari database, parameter URL diabaikan.
- **Riwayat Pembayaran** `/dashboard/payments`: tabel transaksi milik sendiri dengan tautan struk. Menu ada di dropdown akun.
- **Tautan struk** di halaman detail pembayaran (berhasil) dan daftar transaksi di halaman pesanan.

### Hasil testing

`php artisan test`: **207 passed (930 assertions)**. Isinya 198 test sebelumnya ditambah 9 test di `ReceiptTest`:

- isi struk lengkap dari database (Rp1.250.000, komisi Rp125.000, bagian freelancer Rp1.125.000, metode, tanggal, pihak);
- parameter URL tidak mengubah nilai;
- PDF diunduh (`application/pdf`, diawali `%PDF`, nama file benar);
- admin bisa melihat dan mengunduh;
- freelancer dan customer lain 403;
- guest diarahkan ke login;
- pembayaran pending tidak punya struk (404);
- riwayat hanya berisi transaksi sendiri;
- halaman pembayaran berhasil menautkan struk dan PDF.

**Pemeriksaan visual PDF:** contoh struk dirender di dalam transaksi DB yang di-*rollback* (tidak ada data tersisa; dev tetap 3 user dan 0 order). Hasilnya rapi: paket Standard "Website Company Profile Lengkap" Rp3.000.000, komisi 10% Rp300.000, bagian freelancer Rp2.700.000.

### Rencana Hari 11 — Pengiriman hasil dan revisi

1. Tabel `order_deliveries` (pesan dan file privat) serta `order_revisions` (permintaan revisi dan alasan).
2. Freelancer yang ditugaskan mengirim hasil saat status `in_progress` atau `revision_requested`, yang mengubah status menjadi `delivered`. File disimpan di disk **privat** dan diunduh lewat controller yang memeriksa akses.
3. Customer pemilik meminta revisi (jika jatah revisi masih ada, menjadi `revision_requested`) atau menyelesaikan pesanan (menjadi `completed`). Proyek proposal ikut menjadi `completed`.
4. Validasi upload (tipe, ukuran, nama), riwayat status, dan feature test akses semua pihak.

---

## Hari 11 — Pengiriman Hasil dan Revisi

**Tanggal pengerjaan:** 5 November 2026
**Branch:** `hari-11-pengiriman-revisi`, di-merge ke `master`
**Status:** Selesai

### Fitur yang dikerjakan

- **Tabel `order_deliveries`:** order, freelancer, catatan, tautan opsional, metadata file (path privat, nama aman, mime, ukuran). CHECK: wajib ada file **atau** tautan.
- **Tabel `order_revisions`:** order, delivery terkait, peminta, alasan.
- **`OrderWorkflow`** (transaksi + `lockForUpdate` order, memeriksa ulang pihak dan status):
  - `deliver()`: hanya freelancer yang ditugaskan, saat `in_progress`/`revision_requested`; status menjadi `delivered`. Jika transaksi gagal, file yang sudah diunggah dihapus kembali.
  - `requestRevision()`: hanya customer, saat `delivered`, jika jatah masih ada; `revisions_used` naik dan status menjadi `revision_requested`.
  - `complete()`: hanya customer, saat `delivered`; status menjadi `completed`. Proyek asal proposal ikut `completed`.
  - Semua perubahan tercatat di riwayat status.
- **File hasil:**
  - Disimpan di **disk privat `local`** (`storage/app/private/deliveries/{order}/`) dengan nama acak 40 karakter. Nama unduhan disanitasi, contoh `Hasil Map <final>.zip` menjadi `hasil-map-final.zip`.
  - Unduhan lewat `/dashboard/deliveries/{id}/download` hanya untuk customer, freelancer terkait, atau admin.
  - Ekstensi diizinkan: zip, rar, 7z, pdf, png, jpg, jpeg, webp, rbxl, rbxm (file Roblox).
- **Batas ukuran:** `CREATIVEHUB_DELIVERY_MAX_KB` (default 2048 KB = `upload_max_filesize` PHP saat ini). `php.ini` **tidak diubah**. Untuk file lebih besar, freelancer bisa mengirim **tautan** (misalnya Google Drive), atau batas PHP dinaikkan oleh pengguna.
- **Halaman pesanan:**
  - Bagian "Hasil & revisi" menampilkan timeline pengiriman dan revisi.
  - Freelancer mendapat form kirim hasil (catatan, file, tautan).
  - Customer mendapat tombol **Terima Hasil & Selesaikan** dan form **Minta Revisi** dengan sisa jatah.
  - Pesan status untuk setiap aksi.

### Hasil testing

`php artisan test`: **217 passed (988 assertions)**. Isinya 207 test sebelumnya ditambah 10 test di `OrderWorkflowTest`:

- kirim hasil dengan file privat dan nama tersanitasi;
- kirim hanya tautan;
- validasi (tanpa file/tautan, `.exe`, `.php`, lebih dari 2 MB, `javascript:`);
- hanya freelancer yang ditugaskan dan hanya saat dikerjakan (customer, freelancer lain, dan order belum dibayar 403);
- revisi sesuai jatah lalu kirim ulang, setelah jatah habis ditolak;
- selesaikan pesanan dan proyek ikut selesai;
- hanya customer yang meninjau dan hanya setelah hasil dikirim;
- pesanan selesai tidak menerima aksi lagi;
- unduhan file dibatasi pada pihak terkait dan admin;
- form tampil sesuai peran.

Pemeriksaan lain: Pint dan build OK; `migrate` dev (DB `creativehub`) menjalankan 1 migration.

### Rencana Hari 12 — Dashboard customer dan freelancer

1. `/dashboard` berbasis peran:
   - customer: ringkasan pesanan per status, proyek terbuka, pembayaran terbaru, aksi cepat;
   - freelancer: pesanan aktif dan yang perlu dikirim, penawaran menunggu, layanan per status, **ringkasan pendapatan** (dari pesanan selesai/dibayar, bagian freelancer).
2. Halaman `/dashboard/earnings` untuk freelancer: daftar pesanan berbayar dengan nilai, komisi, dan bagian freelancer (pencatatan simulasi).
3. Data hanya milik user login. Feature test isolasi data.
