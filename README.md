# Website-Konveksi

Aplikasi web untuk bisnis konveksi (kaos, kemeja, jaket, seragam, hoodie, dll.), dibangun dengan **PHP Native** dan **MySQL** agar dapat berjalan di **shared hosting** (cPanel) tanpa SSH, Node.js, atau Docker.

## Status

Kode awal (fondasi) sudah tersedia. Lihat [Rancangan Program](RANCANGAN_PROGRAM.md) untuk rencana lengkap.

| Sudah ada | Belum ada (rencana) |
|-----------|---------------------|
| Router, MVC native, PDO, session, CSRF, validator, upload aman (publik & privat) | Pengelolaan tahapan produksi (penugasan, mulai/selesai) |
| Registrasi, login, logout, pembatasan percobaan login, role | Stok bahan baku, supplier, laporan |
| Beranda, katalog (cari, filter, paginasi), detail produk, harga grosir bertingkat | Email/WhatsApp otomatis, ulasan |
| Keranjang dan checkout, form pesanan custom (tabel ukuran, upload desain, min. order) | Kalkulator estimasi, ongkir via API |
| Invoice dan SPK siap cetak (Cetak / Simpan PDF dari peramban), estimasi selesai | |
| Pesanan pelanggan: garis waktu status, bayar DP/pelunasan (upload bukti), setujui/revisi mockup, batalkan | Admin untuk kategori, varian, harga grosir, pelanggan, konten |
| Admin: dashboard, CRUD produk, kelola pesanan (verifikasi bayar, mockup, ongkir, status, resi) | |
| Laporan penjualan, produksi, dan stok: rentang tanggal, ringkasan, tabel rincian, cetak, ekspor CSV | |
| Bahan baku & supplier: stok masuk/pemakaian/koreksi (opname), riwayat, peringatan stok menipis, pemakaian per pesanan; halaman Notifikasi | |
| Pengguna: daftar/cari/filter, tambah staf, ubah peran/status, reset sandi; Akun Saya (profil, ganti sandi) | |
| Produksi: papan produksi, tugas saya, penugasan petugas, mulai/selesai/reset per tahap, progres untuk pelanggan | |
| Skema database lengkap, seed, skrip cron (backup, stok, pengingat) | |

## Persyaratan

PHP ≥ 8.0 (ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, `gd`), MySQL 5.7+/MariaDB 10.3+, Apache dengan `mod_rewrite`.

## Instalasi

1. Buat database MySQL (utf8mb4), lalu impor `database/schema.sql` dan `database/seed.sql` (phpMyAdmin). Bila database dibuat dari versi lama, jalankan berurutan `database/migrations/001_checkout.sql` dan `002_dokumen.sql`.
2. Salin `config/config.example.php` menjadi `config/config.php`, isi kredensial database, `base_path`, dan nomor WhatsApp.
3. Unggah proyek ke hosting:
   - **Ideal**: isi folder `public/` ke `public_html/`, folder lainnya satu tingkat di atas `public_html/`, lalu sesuaikan `define('BASE_DIR', ...)` di `index.php`.
   - **Alternatif**: unggah semuanya ke `public_html/`. `.htaccess` di root mengarahkan ke `public/`, dan `.htaccess` di folder `app/`, `config/`, `database/`, `storage/`, `cron/`, `vendor/` memblokir akses langsung.
4. Pastikan `storage/` dan `public/uploads/` dapat ditulis (755/775). Atur `upload_max_filesize`/`post_max_size` PHP minimal 5 MB (cPanel > Select PHP Version > Options) agar upload desain dan bukti bayar tidak gagal.
5. Ubah nama, alamat, telepon, dan rekening usaha di tabel `settings` (`nama_usaha`, `alamat_usaha`, `telepon_usaha`, `rekening_bank`); nilainya tampil di invoice dan SPK.
6. Login admin bawaan: `admin@konveksi.local` / `admin123`. **Ganti segera lewat menu Akun Saya**; dashboard menampilkan peringatan selama kata sandi bawaan masih dipakai.
7. Aktifkan SSL, lalu buka komentar aturan HTTPS di `public/.htaccess`. Pastikan `debug` bernilai `false` di produksi.

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
Produksi/Admin: kelola 6 tahapan (lihat bawah); tahap QC dimulai -> qc; semua tahap selesai -> menunggu_pelunasan
                 (Keuangan/Admin dapat mengisi ongkir)
Pelanggan: unggah bukti pelunasan        ->  verifikasi  ->  siap_kirim
Produksi/Admin: dikirim (kurir + resi)   ->  selesai
```

Hak akses: **admin** semua; **produksi** mockup & status; **keuangan** verifikasi pembayaran & ongkir. Produk hanya dikelola **admin**. Perpindahan status divalidasi di `Order::allowedNext()`. Berkas desain, mockup, dan bukti bayar disimpan di `storage/uploads` dan hanya dapat dibuka pemilik pesanan atau staf lewat `/berkas/...`.

## Laporan

Menu **Laporan** (`/admin/laporan`) berisi tiga laporan dengan filter rentang tanggal (default bulan berjalan, pilihan cepat Bulan ini/Bulan lalu/30 hari/Tahun ini, maksimal 366 hari), tombol **Cetak**, dan ekspor **CSV**.

| Laporan | Akses | Isi |
|---------|-------|-----|
| Penjualan | admin, keuangan | Omzet (total pesanan dibuat pada periode, tanpa yang dibatalkan), kas masuk (pembayaran terverifikasi pada periode), piutang, jumlah pcs, rata-rata per pesanan; tren per hari (per bulan bila rentang > 62 hari), produk terlaris, pelanggan teratas, pesanan per status, daftar pesanan |
| Produksi | admin, produksi | Pesanan selesai produksi (saat Packing selesai), persentase tepat waktu terhadap estimasi, rata-rata lama produksi, rata-rata durasi per tahap dan per petugas, pesanan sedang berjalan dan terlambat |
| Stok | admin, produksi, keuangan | Posisi stok saat ini dan bahan menipis, stok masuk/keluar pada periode, pemakaian bahan per pesanan |

- **CSV**: UTF-8 dengan BOM. Tersedia pemisah koma dan **titik koma** (pilih titik koma bila Excel berlokal Indonesia menampilkan semua kolom dalam satu kolom). Teks yang berawalan `= + - @` diberi tanda petik agar tidak dijalankan sebagai rumus (mencegah CSV injection). Setiap ekspor dicatat di `activity_logs`.
- Tanggal laporan memakai zona waktu aplikasi (`app.timezone`); sesi MySQL disamakan dengan zona PHP agar batas hari benar meskipun server hosting memakai zona lain.

## Stok Bahan Baku dan Supplier

- **Bahan** (`/admin/bahan`): daftar dengan cari, filter supplier, dan **Hanya yang menipis** (stok sama atau di bawah minimum). Dilihat admin, produksi, dan keuangan. Data bahan (tambah/ubah/hapus) dan supplier hanya admin.
- **Stok hanya berubah lewat mutasi** sehingga selalu cocok dengan riwayat: **Stok Masuk**, **Pemakaian** (boleh dikaitkan ke kode pesanan; tampil di halaman pesanan admin), dan **Koreksi Stok** (hasil hitung fisik; selisih dicatat sebagai penyesuaian). Admin dan produksi boleh mencatat mutasi. Stok tidak boleh negatif dan mutasi diproses atomik (aman bila dua staf mencatat bersamaan).
- **Peringatan stok menipis**: admin mendapat notifikasi saat stok menembus batas minimum (sekali, bukan tiap mutasi), dan cron `cek_stok_minimum.php` mengingatkan harian selama masih menipis dan belum dibaca. Dashboard menampilkan jumlah bahan menipis (klik untuk melihat).
- Bahan yang sudah punya riwayat tidak dapat dihapus. Menghapus supplier membuat bahan terkait menjadi "tanpa supplier".
- **Notifikasi** (`/notifikasi`, semua pengguna): daftar pemberitahuan dengan jumlah belum dibaca di menu, dan "Tandai semua dibaca".

## Pengguna dan Akun

- **Pengguna** (`/admin/pengguna`, khusus admin): cari/filter (peran, status), tambah akun (admin, produksi, keuangan, pelanggan), ubah data/peran/status, dan reset kata sandi. Akun tidak dihapus, hanya **dinonaktifkan** (riwayat pesanan tetap utuh); akun nonaktif tidak bisa login dan sesinya yang sedang berjalan berakhir pada permintaan berikutnya. Admin tidak dapat mengubah peran atau menonaktifkan akunnya sendiri.
- **Akun Saya** (`/akun`, semua pengguna): ubah nama/HP dan ganti kata sandi (wajib memasukkan sandi lama).
- Perubahan penting dicatat di `activity_logs`. Fitur "lupa kata sandi" mandiri belum ada karena butuh email SMTP; sementara admin dapat mereset sandi pengguna.

## Tahapan Produksi

Saat mockup disetujui, enam tahap dibuat otomatis: pemotongan, penjahitan, sablon/bordir, finishing, quality control, packing.

- **Papan produksi** (`/admin/produksi`, admin dan produksi): *Tugas Saya* (tahap yang ditugaskan ke pengguna) dan daftar pesanan dalam produksi dengan progres, tahap aktif, petugas, estimasi, dan penanda **Terlambat**.
- **Per tahap** (di papan atau halaman pesanan admin): pilih petugas, catatan, lalu **Mulai**, **Selesai**, atau **Reset**.
- **Aturan**: tahap dimulai berurutan; tidak bisa selesai sebelum dimulai; staf produksi yang menekan Mulai tanpa memilih petugas otomatis menjadi petugas; Reset hanya jika tahap setelahnya belum dimulai. Perubahan hanya diizinkan saat pesanan berstatus produksi/qc.
- **Otomatis**: memulai tahap QC mengubah status pesanan menjadi `qc`; menyelesaikan tahap terakhir mengubahnya menjadi `menunggu_pelunasan` dan memberi tahu pelanggan nominal pelunasan. Pelanggan menerima notifikasi tiap tahap selesai dan melihat progresnya di halaman pesanan.
- Akun staf dibuat admin di menu **Pengguna** dengan peran Produksi.

## Invoice dan SPK

- **Invoice** (`/pesanan/{kode}/invoice`): dapat dibuka pemilik pesanan dan staf. Memuat rincian item, ongkir, pembayaran terverifikasi, sisa tagihan, dan cap LUNAS / BELUM LUNAS / DIBATALKAN.
- **SPK** (`/admin/pesanan/{kode}/spk`): khusus staf. Memuat matriks jumlah per ukuran dan warna, catatan pelanggan, gambar desain/mockup, estimasi selesai, serta checklist tahapan dengan kolom petugas, tanggal, dan paraf.
- Keduanya berupa halaman HTML siap cetak (A4). Klik **Cetak / Simpan PDF** lalu pilih "Simpan sebagai PDF"; tanpa pustaka tambahan sehingga ringan di shared hosting.

## Struktur Folder

```
public/           front controller (index.php), assets, uploads (satu-satunya yang diakses web)
app/core/         Router, Database (PDO), Auth, Session, Csrf, Validator, Upload, Csv, Controller
app/controllers/  controller publik; admin/ untuk panel admin
app/models/       User, Product, Category, Cart, Order, ProductionStage, Material, Supplier, Report, Setting
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
