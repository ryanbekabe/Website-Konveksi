# Website-Konveksi

Aplikasi web untuk bisnis konveksi (kaos, kemeja, jaket, seragam, hoodie, dll.), dibangun dengan **PHP Native** dan **MySQL** agar dapat berjalan di **shared hosting** (cPanel) tanpa SSH, Node.js, atau Docker.

## Status

Kode awal (fondasi) sudah tersedia. Lihat [Rancangan Program](RANCANGAN_PROGRAM.md) untuk rencana lengkap.

| Sudah ada | Belum ada (rencana) |
|-----------|---------------------|
| Router, MVC native, PDO, session, CSRF, validator, upload aman (publik & privat) | Pengelolaan tahapan produksi (penugasan, mulai/selesai) |
| Registrasi, login, logout, pembatasan percobaan login, role | Stok bahan baku, supplier, laporan |
| Beranda, katalog (cari, filter, paginasi), detail produk, harga grosir bertingkat | Email/WhatsApp otomatis, ulasan |
| Keranjang dan checkout, form pesanan custom (tabel ukuran, upload desain, min. order) | Invoice/SPK PDF, kalkulator estimasi, ongkir via API |
| Pesanan pelanggan: garis waktu status, bayar DP/pelunasan (upload bukti), setujui/revisi mockup, batalkan | Admin untuk kategori, varian, harga grosir, pelanggan, konten |
| Admin: dashboard, CRUD produk, kelola pesanan (verifikasi bayar, mockup, ongkir, status, resi) | |
| Skema database lengkap, seed, skrip cron (backup, stok, pengingat) | |

## Persyaratan

PHP ≥ 8.0 (ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, `gd`), MySQL 5.7+/MariaDB 10.3+, Apache dengan `mod_rewrite`.

## Instalasi

1. Buat database MySQL (utf8mb4), lalu impor `database/schema.sql` dan `database/seed.sql` (phpMyAdmin). Bila database dibuat dari versi lama sebelum checkout, jalankan `database/migrations/001_checkout.sql`.
2. Salin `config/config.example.php` menjadi `config/config.php`, isi kredensial database, `base_path`, dan nomor WhatsApp.
3. Unggah proyek ke hosting:
   - **Ideal**: isi folder `public/` ke `public_html/`, folder lainnya satu tingkat di atas `public_html/`, lalu sesuaikan `define('BASE_DIR', ...)` di `index.php`.
   - **Alternatif**: unggah semuanya ke `public_html/`. `.htaccess` di root mengarahkan ke `public/`, dan `.htaccess` di folder `app/`, `config/`, `database/`, `storage/`, `cron/`, `vendor/` memblokir akses langsung.
4. Pastikan `storage/` dan `public/uploads/` dapat ditulis (755/775). Atur `upload_max_filesize`/`post_max_size` PHP minimal 5 MB (cPanel > Select PHP Version > Options) agar upload desain dan bukti bayar tidak gagal.
5. Login admin bawaan: `admin@konveksi.local` / `admin123`. **Ganti segera setelah login pertama.**
6. Aktifkan SSL, lalu buka komentar aturan HTTPS di `public/.htaccess`. Pastikan `debug` bernilai `false` di produksi.

### Cron Job (cPanel)

```
0 8 * * * /usr/local/bin/php /home/USER/website-konveksi/cron/reminder_pembayaran.php
0 7 * * * /usr/local/bin/php /home/USER/website-konveksi/cron/cek_stok_minimum.php
0 2 * * * /usr/local/bin/php /home/USER/website-konveksi/cron/backup_db.php
```

### Menjalankan secara lokal

```
cp config/config.example.php config/config.php   # lalu sesuaikan
php -S 127.0.0.1:8000 -t public public/index.php
```

## Alur Pesanan

```
Pelanggan: checkout / form pesan custom  ->  menunggu_pembayaran
Pelanggan: unggah bukti DP               ->  Keuangan/Admin verifikasi  ->  dp_dibayar
Produksi/Admin: unggah mockup            ->  menunggu_persetujuan
Pelanggan: setujui  -> produksi (6 tahapan dibuat)   |   revisi -> kembali ke desain_mockup
Produksi/Admin: qc -> menunggu_pelunasan (Keuangan/Admin dapat mengisi ongkir)
Pelanggan: unggah bukti pelunasan        ->  verifikasi  ->  siap_kirim
Produksi/Admin: dikirim (kurir + resi)   ->  selesai
```

Hak akses: **admin** semua; **produksi** mockup & status; **keuangan** verifikasi pembayaran & ongkir. Produk hanya dikelola **admin**. Perpindahan status divalidasi di `Order::allowedNext()`. Berkas desain, mockup, dan bukti bayar disimpan di `storage/uploads` dan hanya dapat dibuka pemilik pesanan atau staf lewat `/berkas/...`.

## Struktur Folder

```
public/           front controller (index.php), assets, uploads (satu-satunya yang diakses web)
app/core/         Router, Database (PDO), Auth, Session, Csrf, Validator, Upload, Controller
app/controllers/  controller publik; admin/ untuk panel admin
app/models/       User, Product, Category, Cart, Order, Setting
app/views/        layouts/, home/, products/, auth/, cart/, admin/
app/helpers/      fungsi bantu global (e(), url(), rupiah(), ...)
app/routes.php    daftar rute
config/           config.example.php (config.php tidak di-commit)
database/         schema.sql, seed.sql
cron/             skrip untuk Cron Job cPanel
storage/          cache, logs, backups, uploads privat (diproteksi .htaccess)
```

Catatan: gambar produk disimpan di `public/uploads/products` (publik, eksekusi PHP dimatikan). Berkas privat seperti bukti bayar dan desain pelanggan nanti disimpan di `storage/uploads` dan disajikan lewat controller yang memeriksa hak akses.

## Menambah Halaman

1. Tambahkan rute di `app/routes.php`: `$router->get('/kontak', 'PageController@contact');`
2. Buat `app/controllers/PageController.php` (extends `Controller`) dengan method `contact()` yang memanggil `$this->view('pages/contact', [...])`.
3. Buat view di `app/views/pages/contact.php`. Selalu escape output dengan `e()` dan sertakan `<?= csrf_field() ?>` di setiap form POST.
