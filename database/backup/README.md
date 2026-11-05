# Backup Database CreativeHub

Salinan isi database (checkpoint 5 November 2026) untuk memulihkan data demo persis seperti saat presentasi.

| File | Isi |
|---|---|
| `creativehub.sql` | Dump PostgreSQL (struktur + data): pengguna, layanan, paket, proyek, penawaran, pesanan, pembayaran simulasi, ulasan, notifikasi, audit log |
| `storage/public/portfolio/` | Gambar portofolio demo (placeholder buatan seeder) |

## Data yang disamarkan (aman untuk repo publik)

- Email akun non-demo diganti menjadi `user{id}@contoh.creativehub.test`.
- **Semua akun memakai password demo yang sama: `CreativeHub-Demo-2026`.** Segera ganti setelah dipulihkan.
- Foto profil tidak disertakan; akun tampil dengan inisial nama.
- Sesi login, cache, antrean, dan token reset password tidak disertakan.

## Cara memulihkan

Database kosong `creativehub` harus sudah ada (lihat README utama), lalu dari folder proyek:

```bash
# 1. Pulihkan struktur + data (menghapus isi tabel yang ada lalu menggantinya)
psql -h 127.0.0.1 -U creativehub -d creativehub -f database/backup/creativehub.sql

# 2. Salin gambar portofolio demo
#    Windows:     xcopy /E /I database\backup\storage\public\portfolio storage\app\public\portfolio
#    Linux/macOS: cp -r database/backup/storage/public/portfolio storage/app/public/
php artisan storage:link
```

Login, misalnya, `admin@demo.creativehub.test` dengan password `CreativeHub-Demo-2026`.

> Alternatif tanpa dump: `php artisan migrate --seed` lalu `php artisan db:seed --class=DemoSeeder` membuat data demo baru dengan password acak.
