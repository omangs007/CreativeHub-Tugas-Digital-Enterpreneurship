# CREATIVEHUB — PROMPT HARI 2

## Katalog Jasa Kreatif — Localhost Development

Kamu adalah **Lead Software Engineer, Laravel Developer, Database Engineer, dan QA Engineer** untuk proyek CreativeHub Marketplace.

### 1. Konteks

Baca terlebih dahulu:

- `CLAUDE_CreativeHub_Revisi.md`: master blueprint (aturan bisnis, keamanan, UI/UX).
- `ROADMAP_CreativeHub_Revisi_21_Hari.md`: roadmap.
- `creativehub/docs/PROGRESS.md`: hasil Hari 1, keputusan teknis, dan catatan.

Keputusan yang sudah berlaku dan tidak boleh diubah tanpa persetujuan:

- Localhost saja: `http://127.0.0.1:8000`, PostgreSQL 17 lokal (database `creativehub`, test di `creativehub_test`).
- Laravel 13, Breeze stack Blade, Livewire 4, Filament 5 (`/admin`), Tailwind CSS 4 via Vite.
- Role: tabel `roles` + `user_roles`. Gunakan `User::hasRole(RoleName::...)`, middleware `role:...`, dan Policy. Jangan pernah mempercayai role atau harga dari input browser.
- UI Bahasa Indonesia, mata uang Rupiah (BIGINT, tanpa float), zona waktu `Asia/Jakarta`.

### 2. Target Hari 2

Kerjakan **HARI 2 SAJA: Kategori & Katalog Jasa**.

1. Migration, model, factory:
   - `categories`: name, slug unique, description, icon, is_active, sort_order.
   - `services`: freelancer `user_id` FK, `category_id` FK, title, slug unique, description, thumbnail_path nullable, status `draft|pending_review|published|rejected` ber-index, published_at.
   - `service_packages`: `service_id` FK cascade, tier `basic|standard|premium`, name, description, price BIGINT (Rupiah, > 0), delivery_days, revisions, is_active. Unique `(service_id, tier)`.
2. Seeder kategori wajib: Roblox Map, Website Development, Poster/Graphic Design. Data referensi ini harus idempoten (`updateOrCreate`).
3. Layout publik CreativeHub: navbar (logo, Katalog, Masuk/Daftar atau Dashboard), footer, font Plus Jakarta Sans atau Inter, palet navy/charcoal dengan aksen violet. Harus responsif.
4. Homepage `/`: hero, kategori populer, layanan pilihan dari database, CTA. Tampilkan empty state jika belum ada layanan.
5. Halaman publik:
   - `/services`: katalog dengan pencarian judul dan filter kategori, paginasi.
   - `/categories/{slug}`: layanan per kategori.
   - `/services/{slug}`: detail layanan, profil singkat freelancer, tabel paket Basic/Standard/Premium aktif, harga format `Rp150.000`.
6. Aturan visibilitas:
   - Hanya layanan `published` dengan kategori aktif yang tampil ke publik.
   - Layanan draft, pending, atau rejected memberi 404 untuk publik.
   - Hanya paket `is_active` yang tampil.
7. Helper format Rupiah dan tanggal Indonesia (misalnya Blade component atau helper kecil).
8. Data demo katalog lewat factory/seeder terpisah (`CatalogDemoSeeder`) yang jelas ditandai sebagai data demo. Jangan memakai password akun yang di-hardcode; jika butuh akun freelancer demo, buat password acak lalu tampilkan sekali di output seeder.

Jangan mengerjakan CRUD layanan oleh freelancer (Hari 6), moderasi admin (Hari 7), checkout, pembayaran, proyek/proposal, atau chat.

### 3. Testing wajib

- Guest dapat membuka `/`, `/services`, `/categories/{slug}`, `/services/{slug}`.
- Layanan draft/pending/rejected tidak muncul di katalog dan detailnya 404.
- Layanan dengan kategori nonaktif tidak muncul.
- Pencarian dan filter kategori mengembalikan hasil yang benar.
- Detail layanan hanya menampilkan paket aktif, dan harga terformat Rupiah.
- Constraint database: slug unik, satu paket per tier per layanan.
- Seluruh test Hari 1 tetap lulus.

Jangan menyatakan test berhasil sebelum `php artisan test` benar-benar dijalankan.

### 4. Definition of Done Hari 2

- [ ] Migration kategori, layanan, dan paket berhasil di PostgreSQL (`migrate:fresh` bersih).
- [ ] Kategori wajib tersedia lewat seeder.
- [ ] Homepage dan katalog tampil dengan layout CreativeHub, responsif.
- [ ] Hanya layanan published yang terlihat publik.
- [ ] Detail layanan menampilkan paket dan harga Rupiah.
- [ ] Semua test lulus (Hari 1 + Hari 2), `vendor/bin/pint --test` lulus, `npm run build` sukses.
- [ ] `docs/PROGRESS.md` diperbarui (tanggal, file, command, hasil test, error, rencana Hari 3).

Setelah selesai, berhenti dan tunggu persetujuan sebelum lanjut ke Hari 3.
