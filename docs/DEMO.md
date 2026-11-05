# Naskah Demo CreativeHub (± 15 menit)

Panduan presentasi ke dosen. Semua langkah memakai data dari `DemoSeeder`. Password akun demo ada di file lokal milik penyaji (tidak ada di repo).

## Persiapan (sebelum presentasi)

```bash
# Database bersih + data demo (HATI-HATI: menghapus semua data di database development)
php artisan migrate:fresh --seed
php artisan db:seed --class=DemoSeeder      # catat password akun demo yang ditampilkan
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Checklist:

- [ ] `http://127.0.0.1:8000` terbuka dan beranda menampilkan 6 layanan dan rating ★5,0.
- [ ] Siapkan tiga jendela browser (atau satu normal + dua incognito) untuk **customer** (`budi@demo.creativehub.test`), **freelancer** (`dimas@demo.creativehub.test`), dan **admin** (`admin@demo.creativehub.test`).
- [ ] Zoom browser 100–110% agar terbaca di proyektor.

## 1. Guest (1 menit)

1. Buka beranda: hero, kategori (Roblox Map, Website, Poster), layanan terbaru dengan rating nyata, freelancer unggulan.
2. **Katalog Jasa**: cari "poster", filter kategori, filter harga dan urutan "Harga terendah".
3. Buka detail **Desain Poster Event & Promosi**: tab paket Basic/Standard/Premium, ulasan, dan profil freelancer (klik nama Dimas).
4. Tekan **Pesan Paket**: guest diarahkan ke halaman **login** (aturan akses).

## 2. Customer membeli paket + pembayaran simulasi + struk (4 menit) — *kebutuhan wajib 1 & 2*

Masuk sebagai **budi**.

1. Detail layanan **Desain Poster Event & Promosi**, **Pesan Paket Standard**, centang persetujuan, **Buat Pesanan**.
   - Tunjukkan: harga diambil dari server; snapshot ruang lingkup; status **Menunggu Pembayaran**.
2. **Bayar Sekarang**: tunjukkan banner **"Mode Demo — pembayaran tidak menggunakan uang sungguhan"** dan tiga metode (QRIS, Virtual Account, E-Wallet simulasi).
3. Pilih **Transfer Virtual Account**: nomor VA dummy, lalu **Simulasikan Gagal**. Status pembayaran Gagal, pesanan tetap menunggu pembayaran.
4. **Coba bayar lagi**, pilih **QRIS**, lalu **Simulasikan Berhasil**. Pesanan menjadi **Dikerjakan**; buka log transaksi.
5. **Lihat / Cetak Struk**: identitas CreativeHub, nomor transaksi, tanggal, customer, freelancer, paket, rincian, komisi, total, metode, status. Klik **Cetak Struk** lalu **Unduh PDF**.
6. Poin keamanan: buka ulang URL halaman pembayaran (atau tambahkan `?status=success`); status tidak berubah tanpa proses server.

## 3. Proyek dan penawaran (3 menit)

1. Masih sebagai **budi**: **Proyek Saya**, **Posting Proyek** (contoh: "Poster lomba futsal kampus", anggaran Rp100.000–Rp300.000).
2. Di jendela **dimas**: menu **Freelancer**, **Cari Proyek**, buka proyek Budi, lalu **Kirim Penawaran**.
3. Kembali ke **budi**: lonceng **notifikasi** "Penawaran baru". Buka proyek, **Tanya freelancer** (chat), lalu **Terima Penawaran**. Pesanan otomatis dibuat; penawaran lain ditolak. Bayar dengan simulasi berhasil.

## 4. Pengerjaan, revisi, selesai, ulasan (2 menit)

1. **dimas**: lonceng "Pesanan baru dibayar", buka pesanan, **Kirim Hasil** (isi tautan atau unggah file ≤ 2 MB).
2. **budi**: **Minta Revisi**. **dimas** kirim ulang hasil. **budi**: **Terima Hasil & Selesaikan**, lalu **Beri ulasan** ★5.
3. **dimas**: **Freelancer**, **Pendapatan**: pendapatan selesai bertambah (pencatatan simulasi).

## 5. Admin + laporan keuangan (4 menit) — *kebutuhan wajib 3*

Masuk ke `/admin` sebagai **admin**.

1. **Dasbor**: kartu omzet bulan ini, grafik omzet 30 hari, layanan menunggu review.
2. **Layanan**: filter "Menunggu Review", lalu **Setujui** atau **Tolak** dengan alasan. **Audit Log** mencatatnya.
3. **Pesanan** dan **Pembayaran**: hanya baca (nilai transaksi tidak bisa diubah admin).
4. **Laporan Keuangan**:
   - preset **Bulan ini**, lalu **Bulan tertentu** (bulan lalu), lalu **Rentang tanggal** (awal bulan lalu s.d. hari ini);
   - jelaskan: **omzet hanya transaksi berhasil**; pending/gagal/dibatalkan dihitung terpisah; omzet = komisi + bagian freelancer;
   - **Cetak Laporan** dan **Unduh PDF**, lalu cocokkan angka PDF dengan halaman.
5. **Pengaturan Komisi**: ubah 10% menjadi 12,5%. Hanya berlaku untuk pesanan baru (tercatat di audit log).
6. **Pengguna**: nonaktifkan satu customer. Akun tidak bisa login (opsional untuk didemokan).

## 6. Bukti kualitas (1 menit, opsional)

```bash
php artisan test     # 277 test otomatis lulus
```

Sebutkan: aturan bisnis dijaga di database (CHECK/unique index), uji akses (IDOR) pada 16 endpoint, dan semua nominal integer Rupiah.

## Jika terjadi masalah saat demo

| Gejala | Tindakan |
|---|---|
| Halaman tanpa gaya/CSS | `npm run build` |
| Gambar portofolio tidak muncul | `php artisan storage:link` |
| "Tagihan sudah kedaluwarsa" | Buat pembayaran baru dari tombol **Bayar Sekarang** |
| Lupa password akun demo | `php artisan app:create-admin` untuk admin baru, atau ulangi persiapan |
| Error 419 (sesi kedaluwarsa) | Muat ulang halaman |
