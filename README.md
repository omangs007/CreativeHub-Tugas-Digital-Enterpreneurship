# CreativeHub

Marketplace jasa kreatif lokal (proyek tugas kuliah): **desain map Roblox, pembuatan website, poster dan desain grafis**.
Customer bisa memesan **paket jasa** atau **memposting proyek** lalu menerima penawaran freelancer.
Aplikasi hanya dijalankan di komputer lokal.

> **Mode demo:** semua pembayaran adalah **simulasi**. Tidak ada uang sungguhan yang berpindah, tidak terhubung ke bank, QRIS, atau e-wallet mana pun, dan pembagian komisi hanya pencatatan.

## Fitur utama

| Peran | Fitur |
|---|---|
| **Guest** | Beranda, katalog jasa (cari, filter kategori dan harga, urutan), detail jasa dan paket, profil freelancer dan portofolio, daftar proyek terbuka, daftar/masuk |
| **Customer** | Pesan paket, posting proyek, terima satu penawaran, **pembayaran simulasi** (QRIS/VA/e-wallet; berhasil, pending, gagal), **struk** (cetak dan PDF), tinjau hasil, minta revisi, selesaikan pesanan, ulasan, chat, batalkan pesanan yang belum dibayar |
| **Freelancer** | Profil, portofolio (upload gambar), CRUD layanan dan paket Basic/Standard/Premium, ajukan review, kirim penawaran, kirim hasil (file privat/tautan), chat, ringkasan pendapatan |
| **Admin** (`/admin`) | Dasbor statistik, moderasi layanan (setujui/tolak), kategori, pengguna (aktif/nonaktif), pesanan dan pembayaran (hanya baca), ulasan, proyek, portofolio, **pengaturan komisi**, **audit log**, **laporan keuangan** (filter tanggal dan preset, cetak, PDF) |

Satu akun bisa menjadi Customer sekaligus Freelancer (aktifkan dari halaman Profil). Admin hanya dibuat lewat perintah artisan.

## Teknologi

Laravel 13, PHP 8.4, PostgreSQL 17, Blade + Tailwind CSS 4 + Alpine.js, Livewire 4, Filament 5 (panel admin), DomPDF (struk dan laporan), Vite 7.

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`, `bcmath`
- Composer 2, Node.js 20+ dan npm
- PostgreSQL 15+ (dikembangkan dengan 17)

> Di Windows, aktifkan `extension=pdo_pgsql` dan `extension=pgsql` di `php.ini`.

## Instalasi lokal

```bash
# 1. Siapkan database (sekali saja), dengan psql sebagai superuser:
#    CREATE ROLE creativehub LOGIN PASSWORD 'ganti-password';
#    CREATE DATABASE creativehub OWNER creativehub;
#    CREATE DATABASE creativehub_test OWNER creativehub;   -- untuk test otomatis

# 2. Dependensi
composer install
npm install

# 3. Konfigurasi
cp .env.example .env            # Windows: copy .env.example .env
# isi DB_PASSWORD di .env
php artisan key:generate

# 4. Database dan file
php artisan migrate
php artisan storage:link
php artisan db:seed                         # kategori wajib
php artisan db:seed --class=DemoSeeder      # data demo lengkap (opsional, untuk presentasi)

# 5. Akun admin milik Anda sendiri (interaktif)
php artisan app:create-admin

# 6. Jalankan
npm run build                                # atau `npm run dev` di terminal terpisah
php artisan serve --host=127.0.0.1 --port=8000
```

- Website: <http://127.0.0.1:8000>
- Panel admin: <http://127.0.0.1:8000/admin>
- Laporan keuangan: <http://127.0.0.1:8000/admin/financial-reports> (juga ada di menu admin, grup Transaksi)

## Data dan akun demo

`DemoSeeder` membuat data yang **ditandai sebagai demo** (email `@demo.creativehub.test`):

| Akun | Peran |
|---|---|
| `admin@demo.creativehub.test` | Admin |
| `budi@`, `siti@`, `andi@demo.creativehub.test` | Customer |
| `raka@` (Roblox), `nadia@` (Website), `dimas@demo.creativehub.test` (Desain) | Customer + Freelancer |

**Password dibuat acak** dan hanya ditampilkan sekali saat seeder membuat akun. Jangan memakai password akun pribadi.
Data transaksi demo mencakup pesanan selesai dengan ulasan, sedang dikerjakan, menunggu tinjauan, menunggu pembayaran, pembayaran gagal, dan dibatalkan. Tanggalnya tersebar di bulan lalu dan bulan ini agar filter laporan bisa didemonstrasikan. Seeder aman dijalankan berulang.

## Skenario demo

1. **Customer membeli paket:** masuk sebagai customer, Katalog, pilih jasa, **Pesan Paket**, setujui, **Bayar Sekarang**, pilih metode, **Simulasikan Berhasil**, lalu **Lihat/Cetak Struk** atau **Unduh PDF**.
2. **Proyek dan penawaran:** customer **Posting Proyek**; freelancer lain mengirim penawaran dari halaman proyek; customer **Terima Penawaran** (penawaran lain otomatis ditolak); pesanan dibuat dan dibayar.
3. **Pengerjaan:** freelancer **Kirim Hasil**; customer **Minta Revisi** (sesuai jatah); freelancer kirim ulang; customer **Terima Hasil & Selesaikan**, lalu beri ulasan.
4. **Laporan keuangan:** admin membuka **Laporan Keuangan**, pilih preset atau rentang tanggal, cocokkan ringkasan dengan tabel, lalu **Cetak** atau **Unduh PDF**.
5. **Keamanan peran:** guest tidak bisa membuka dashboard; customer tidak bisa membuka pesanan/struk orang lain atau `/admin`; URL "sukses" tidak mengubah status pembayaran.

## Aturan bisnis penting

- Harga dan komisi dihitung **server**. Nominal disimpan sebagai **integer Rupiah**. Order menyimpan **snapshot** judul, scope, dan harga.
- Order bersumber **tepat satu**: paket layanan atau penawaran proyek (CHECK constraint database).
- Maksimal **satu penawaran diterima per proyek** (unique index parsial + `lockForUpdate`).
- Pembayaran berhasil **hanya sekali per order** (unique index parsial), idempoten, nominal diverifikasi. Status hanya berubah lewat POST server, tidak lewat URL.
- File hasil pekerjaan disimpan di disk **privat** dan diunduh lewat otorisasi. Gambar portofolio/thumbnail tervalidasi (tanpa SVG).
- Aksi admin yang memengaruhi akun, moderasi, dan komisi dicatat di **audit log**.
- Omzet di laporan = Σ pembayaran **berhasil** (berdasarkan tanggal bayar). Pending, gagal, kedaluwarsa, dan dibatalkan dihitung terpisah.

## Pengujian

```bash
php artisan test        # memakai database creativehub_test (lihat phpunit.xml)
vendor/bin/pint --test  # gaya kode
```

> Jangan menjalankan perintah artisan destruktif dengan `--env=testing`. Tidak ada `.env.testing`, sehingga perintah tersebut akan mengenai database development.

## Konfigurasi tambahan (`.env`)

| Variabel | Default | Keterangan |
|---|---|---|
| `CREATIVEHUB_COMMISSION_BPS` | `1000` | Komisi default (basis poin, 1000 = 10%). Admin bisa mengubahnya di panel. |
| `CREATIVEHUB_DELIVERY_MAX_KB` | `2048` | Ukuran maksimal file hasil. Harus ≤ `upload_max_filesize` dan `post_max_size` PHP. Untuk file besar, gunakan tautan (misalnya Google Drive). |

## Batasan MVP

Tidak termasuk: hosting/deployment, pembayaran dan payout sungguhan, escrow, aplikasi mobile, chat real-time (memakai polling HTTP), multi-currency, dan sistem sengketa. Arsitektur `PaymentGateway` sudah siap bila integrasi provider sungguhan (misalnya Midtrans) dibutuhkan kemudian.

Riwayat pengerjaan per hari: lihat [`docs/PROGRESS.md`](docs/PROGRESS.md).
