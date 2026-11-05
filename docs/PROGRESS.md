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
