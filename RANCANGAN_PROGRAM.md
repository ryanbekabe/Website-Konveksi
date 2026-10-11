# Rancangan Program Website Konveksi (PHP Native + Shared Hosting)

## 1. Ringkasan Proyek

Website konveksi adalah aplikasi web untuk bisnis produksi pakaian (kaos, kemeja, jaket, seragam, hoodie, dll.) yang dibangun dengan **PHP Native (tanpa framework)** dan **MySQL**, sehingga dapat berjalan di **shared hosting biasa** (cPanel) tanpa akses SSH, Node.js, Docker, atau Redis.

Cakupan:

1. **Company profile & katalog**: produk, portofolio, informasi usaha.
2. **Pemesanan online**: produk ready stock dan custom (sablon/bordir).
3. **Manajemen produksi**: pelacakan status dari desain hingga pengiriman.
4. **Panel admin**: produk, pesanan, pembayaran, stok bahan, pelanggan, laporan.

### Tujuan
- Mempermudah pelanggan memesan dan memantau pesanan secara online.
- Menggantikan pencatatan manual (WhatsApp/kertas) dengan sistem terpusat.
- Menyediakan data penjualan dan produksi untuk pengambilan keputusan.

### Batasan Shared Hosting yang Dipertimbangkan
| Batasan | Dampak pada Rancangan |
|---------|-----------------------|
| Tidak ada SSH / Composer / Node | Semua dependensi disertakan manual di folder `vendor/` (upload via File Manager/FTP); tanpa proses build |
| Tidak ada proses background / queue | Notifikasi dikirim langsung (sinkron); tugas terjadwal memakai **Cron Job cPanel** |
| Batas memori & waktu eksekusi PHP | Pagination, batas ukuran upload, laporan diproses per halaman |
| Tidak ada Redis | Cache sederhana berbasis file |
| Satu akun hosting, folder `public_html` | Hanya folder `public/` yang dapat diakses web; sisanya di luar atau diproteksi `.htaccess` |
| Versi PHP 8.x, MySQL/MariaDB | Kode kompatibel PHP ≥ 8.0, PDO MySQL, `utf8mb4` |

---

## 2. Pengguna (Aktor)

| Aktor | Deskripsi |
|-------|-----------|
| **Pengunjung** | Melihat katalog, portofolio, informasi. |
| **Pelanggan** | Terdaftar; memesan, membayar, melacak order. |
| **Admin** | Pemilik/manajer; mengelola seluruh sistem. |
| **Staf Produksi** | Memperbarui tahapan produksi. |
| **Staf Keuangan** | Verifikasi pembayaran dan laporan keuangan. |

---

## 3. Fitur Utama

### 3.1 Sisi Publik
- Beranda: banner, layanan, produk populer, testimoni, tombol WhatsApp.
- Katalog produk: filter kategori/bahan/harga, pencarian, detail produk (ukuran, warna, harga grosir bertingkat).
- Portofolio, Tentang Kami, Blog/Artikel (SEO), FAQ, Kontak (form + peta).
- **Kalkulator estimasi harga**: jenis produk, bahan, jumlah, sablon/bordir → estimasi harga & waktu (dihitung di server, dengan JavaScript ringan untuk tampilan).

### 3.2 Sisi Pelanggan
- Registrasi/login (email atau nomor HP).
- Profil & buku alamat.
- **Pemesanan**:
  - Ready stock: keranjang → checkout.
  - Custom order: pilih produk, upload desain, isi tabel ukuran, catatan, jumlah.
  - Permintaan penawaran (RFQ) untuk pesanan besar.
- Persetujuan mockup (setuju / minta revisi).
- **Pembayaran**: transfer bank manual dengan upload bukti (DP dan pelunasan). Opsional: Midtrans/Xendit Snap (hanya butuh cURL + endpoint callback).
- Pelacakan pesanan (timeline status), riwayat, invoice (cetak/PDF), pesan ulang.
- Ulasan & rating.

### 3.3 Sisi Admin
- Dashboard ringkasan (pesanan baru, order terlambat, stok menipis, omzet).
- Manajemen produk (kategori, varian, harga bertingkat, gambar).
- Manajemen pesanan: detail, ubah status, cetak invoice & **SPK** (surat perintah kerja).
- Manajemen produksi: tahapan, penugasan staf, estimasi selesai (tampilan papan per tahap).
- Verifikasi pembayaran dan refund.
- Stok bahan baku & supplier, peringatan stok minimum.
- Pelanggan, konten (banner, portofolio, blog, FAQ, testimoni).
- Laporan penjualan/produksi/keuangan, ekspor **CSV/Excel** (CSV native) dan cetak PDF.
- Pengguna, hak akses (role), pengaturan, log aktivitas.

### 3.4 Fitur Pendukung
- Notifikasi email (SMTP/PHPMailer) dan tautan WhatsApp (`wa.me` dengan pesan terisi; WhatsApp API opsional).
- Ongkir: input manual per wilayah, atau API RajaOngkir/Biteship via cURL.
- SEO: meta tag, sitemap.xml dinamis, URL ramah (`.htaccess`).
- Responsif / mobile-first.

---

## 4. Alur Proses Bisnis

### 4.1 Alur Pemesanan Custom
```
Pelanggan pilih produk & isi spesifikasi
        ↓
Upload desain + tabel ukuran
        ↓
Sistem hitung estimasi  ──►  (opsional) Admin kirim penawaran
        ↓
Pelanggan konfirmasi → bayar DP (upload bukti)
        ↓
Admin verifikasi DP → buat mockup
        ↓
Pelanggan setujui mockup (atau minta revisi)
        ↓
Produksi: Potong → Jahit → Sablon/Bordir → QC → Packing
        ↓
Pelunasan → Pengiriman (input resi)
        ↓
Selesai → Ulasan
```

### 4.2 Status Pesanan
`menunggu_pembayaran` → `dp_dibayar` → `desain_mockup` → `menunggu_persetujuan` → `produksi` → `qc` → `menunggu_pelunasan` → `siap_kirim` → `dikirim` → `selesai` (atau `dibatalkan`).

### 4.3 Tahapan Produksi
`pemotongan` → `penjahitan` → `sablon_bordir` → `finishing` → `qc` → `packing`.

---

## 5. Arsitektur Sistem

### 5.1 Teknologi

| Lapisan | Pilihan |
|---------|---------|
| Bahasa | PHP Native ≥ 8.0, OOP ringan + pola **MVC sederhana** buatan sendiri |
| Database | MySQL/MariaDB via **PDO** (prepared statement) |
| Web server | Apache (`mod_rewrite`, `.htaccess`) |
| Frontend | HTML, CSS (Bootstrap 5 atau Tailwind via CDN/berkas statis), JavaScript vanilla / Alpine.js |
| Template | Berkas PHP biasa dengan layout dan fungsi `e()` untuk escape output |
| Email | PHPMailer (disalin manual ke `vendor/`) |
| PDF | Dompdf / mPDF (disalin manual) atau cetak HTML lewat browser |
| Penyimpanan file | Folder `storage/uploads` (di luar `public/` bila memungkinkan) |
| Cache | Berkas di `storage/cache` |
| Tugas terjadwal | Cron Job cPanel memanggil `php cron/xxx.php` |
| Autentikasi | Session PHP + `password_hash()` (bcrypt/argon2), RBAC |

### 5.2 Alur Permintaan (Front Controller)
```
Browser ──► public/index.php ──► Router ──► Controller ──► Model (PDO) ──► MySQL
                                              │
                                              └──► View (template PHP) ──► HTML
```
Semua permintaan masuk lewat `public/index.php` (diatur `.htaccess`), lalu diteruskan Router ke Controller. Controller memanggil Model, kemudian merender View.

### 5.3 Opsi Penempatan di Hosting
- **Ideal**: isi folder `public/` ke `public_html/`, folder lain (`app/`, `config/`, `storage/`, `vendor/`) satu tingkat di atas `public_html/`.
- **Jika tidak bisa di luar `public_html`**: letakkan semua di `public_html/`, tambahkan `.htaccess` `Deny from all` di folder `app/`, `config/`, `storage/`, `vendor/`, `database/`, dan arahkan root ke `public/` lewat `.htaccess` di root.

---

## 6. Struktur Direktori

```
website-konveksi/
├── public/                    # satu-satunya folder yang diakses web
│   ├── index.php              # front controller
│   ├── .htaccess              # rewrite URL ramah
│   └── assets/                # css, js, img
├── app/
│   ├── core/                  # Router, Controller, Model, Database, Auth, Session, Csrf, View, Validator
│   ├── controllers/
│   │   ├── HomeController.php
│   │   ├── ProductController.php
│   │   ├── CartController.php
│   │   ├── OrderController.php
│   │   ├── AuthController.php
│   │   └── admin/             # Dashboard, Product, Order, Production, Payment, Material, Report, User
│   ├── models/                # User, Product, Order, Payment, Production, Material, ...
│   ├── views/
│   │   ├── layouts/           # main.php, admin.php
│   │   ├── home/ products/ cart/ orders/ auth/
│   │   └── admin/
│   ├── helpers/               # format rupiah, tanggal, slug, upload, mailer
│   └── routes.php             # daftar rute
├── config/
│   ├── config.php             # base_url, db, smtp, dll. (tidak di-commit; sediakan config.example.php)
│   └── config.example.php
├── cron/                      # skrip untuk Cron Job cPanel
│   ├── reminder_pembayaran.php
│   ├── cek_stok_minimum.php
│   └── backup_db.php
├── database/
│   ├── schema.sql             # struktur tabel
│   └── seed.sql               # data awal (admin, kategori, pengaturan)
├── storage/
│   ├── uploads/               # desain, mockup, bukti bayar (dilindungi)
│   ├── cache/
│   └── logs/
├── vendor/                    # PHPMailer, Dompdf (disalin manual)
├── .htaccess
├── .gitignore
└── README.md
```

---

## 7. Rancangan Basis Data (MySQL)

### 7.1 Tabel Utama

| Tabel | Kolom Utama |
|-------|-------------|
| `users` | id, nama, email, no_hp, password_hash, role (`admin`/`produksi`/`keuangan`/`pelanggan`), status, created_at |
| `addresses` | id, user_id, penerima, no_hp, alamat, kota, provinsi, kode_pos |
| `categories` | id, nama, slug, deskripsi |
| `products` | id, category_id, nama, slug, deskripsi, bahan, harga_dasar, tipe (`ready`/`custom`), aktif |
| `product_variants` | id, product_id, ukuran, warna, sku, stok, harga |
| `price_tiers` | id, product_id, min_qty, max_qty, harga |
| `product_images` | id, product_id, file, urutan |
| `carts` / `cart_items` | id, user_id, product_id, variant_id, qty |
| `orders` | id, kode_order, user_id, tipe, status, subtotal, ongkir, diskon, total, catatan, estimasi_selesai, created_at |
| `order_items` | id, order_id, product_id, variant_id, qty, harga_satuan, spesifikasi (JSON/TEXT) |
| `order_designs` | id, order_id, file, tipe (`desain`/`mockup`), status_approval, catatan |
| `payments` | id, order_id, jenis (`dp`/`pelunasan`), metode, jumlah, bukti, status, verified_by, dibayar_pada |
| `shipments` | id, order_id, kurir, layanan, resi, biaya, status |
| `production_stages` | id, order_id, tahap, petugas_id, status, mulai, selesai, catatan |
| `materials` | id, nama, satuan, stok, stok_minimum, supplier_id |
| `material_movements` | id, material_id, tipe (`masuk`/`keluar`), qty, order_id, tanggal |
| `suppliers` | id, nama, kontak, alamat |
| `reviews` | id, user_id, product_id, order_id, rating, komentar, tampil |
| `portfolios` | id, judul, deskripsi, gambar, kategori |
| `posts` | id, judul, slug, konten, status, published_at |
| `settings` | key, value (nama usaha, rekening bank, kontak, tarif ongkir) |
| `notifications` | id, user_id, pesan, dibaca, created_at |
| `activity_logs` | id, user_id, aksi, objek, detail, ip, created_at |

### 7.2 Relasi Inti
- `users` 1—N `orders`; `orders` 1—N `order_items`, `payments`, `production_stages`, `order_designs`; `orders` 1—1 `shipments`.
- `categories` 1—N `products`; `products` 1—N `product_variants`, `price_tiers`, `product_images`.
- `suppliers` 1—N `materials`; `materials` 1—N `material_movements`.

Catatan: gunakan mesin **InnoDB**, charset `utf8mb4`, foreign key, dan indeks pada `slug`, `kode_order`, `status`, `user_id`.

---

## 8. Rancangan Rute (Router Native)

| Area | Rute | Metode |
|------|------|--------|
| Publik | `/`, `/produk`, `/produk/{slug}`, `/portofolio`, `/blog`, `/blog/{slug}`, `/tentang`, `/kontak`, `/estimasi` | GET/POST |
| Auth | `/masuk`, `/daftar`, `/keluar`, `/lupa-password` | GET/POST |
| Keranjang | `/keranjang`, `/keranjang/tambah`, `/keranjang/hapus` | GET/POST |
| Pesanan | `/pesan`, `/pesan/custom`, `/pesanan`, `/pesanan/{kode}`, `/pesanan/{kode}/bayar`, `/pesanan/{kode}/mockup`, `/pesanan/{kode}/invoice` | GET/POST |
| Admin | `/admin`, `/admin/pesanan`, `/admin/produksi`, `/admin/produk`, `/admin/pembayaran`, `/admin/bahan`, `/admin/pelanggan`, `/admin/konten`, `/admin/laporan`, `/admin/pengguna`, `/admin/pengaturan` | CRUD |
| Callback | `/pembayaran/callback` (jika memakai payment gateway) | POST |

Contoh `public/.htaccess`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

## 9. Rancangan Antarmuka

**Publik**: Beranda · Katalog · Detail Produk · Portofolio · Tentang · Blog · Kontak · Kalkulator Harga
**Pelanggan**: Masuk/Daftar · Keranjang · Checkout · Form Custom Order · Daftar & Detail Pesanan (timeline) · Profil
**Admin**: Dashboard · Pesanan · Produksi · Produk · Stok Bahan · Pembayaran · Pelanggan · Konten · Laporan · Pengguna · Pengaturan

Prinsip: mobile-first, navigasi sederhana, tombol WhatsApp melayang, gambar dikompres otomatis saat upload (GD), halaman ringan agar cepat di shared hosting.

---

## 10. Keamanan (Wajib pada PHP Native)

- **SQL Injection**: semua query memakai PDO prepared statement.
- **XSS**: escape semua output dengan `htmlspecialchars()` (helper `e()`).
- **CSRF**: token pada setiap form POST, divalidasi di server.
- **Password**: `password_hash()` / `password_verify()`; regenerasi session ID setelah login; cookie `HttpOnly`, `Secure`, `SameSite`.
- **Upload file**: validasi MIME (`finfo`) dan ekstensi, batasi ukuran, ganti nama file acak, simpan di luar akses publik, tampilkan lewat skrip PHP yang mengecek hak akses; larang eksekusi PHP di folder upload (`.htaccess`).
- **Otorisasi**: pemeriksaan role di setiap controller admin; pelanggan hanya dapat melihat pesanannya sendiri.
- **Rate limiting login**: batasi percobaan gagal berdasarkan IP/akun (tabel `login_attempts`).
- **Konfigurasi**: `config.php` di luar `public/` dan tidak masuk repositori; `display_errors` dimatikan di produksi, error dicatat ke `storage/logs`.
- **Header**: `X-Frame-Options`, `X-Content-Type-Options`, paksa HTTPS lewat `.htaccess`.
- **Backup**: cron harian ekspor database ke `storage/backups` (dan unduh berkala).

---

## 11. Kebutuhan Non-Fungsional

- **Kompatibilitas**: PHP ≥ 8.0, MySQL 5.7+/MariaDB 10.3+, ekstensi `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `curl`.
- **Performa**: pagination, indeks DB, cache berkas untuk data yang jarang berubah, gambar WebP/terkompresi, lazy load.
- **Ketersediaan**: backup rutin, log error.
- **Kegunaan**: responsif, bahasa Indonesia.
- **Kepatuhan**: kebijakan privasi, perlindungan data pribadi (UU PDP).

---

## 12. Panduan Deployment di Shared Hosting

1. Buat database dan user MySQL di cPanel, impor `database/schema.sql` lalu `database/seed.sql` lewat phpMyAdmin.
2. Salin `config/config.example.php` menjadi `config/config.php`, isi kredensial database, `base_url`, SMTP.
3. Unggah berkas via File Manager/FTP (atau ZIP lalu ekstrak), sesuai opsi penempatan di bagian 5.3.
4. Pilih versi PHP 8.x di cPanel (*Select PHP Version*) dan aktifkan ekstensi yang dibutuhkan.
5. Pastikan folder `storage/` dapat ditulis (permission 755/775).
6. Aktifkan SSL (AutoSSL/Let's Encrypt) dan paksa HTTPS.
7. Ganti kata sandi admin bawaan setelah login pertama.
8. Atur **Cron Job** di cPanel, misalnya:
   - `0 8 * * * /usr/local/bin/php /home/USER/website-konveksi/cron/reminder_pembayaran.php`
   - `0 7 * * * /usr/local/bin/php /home/USER/website-konveksi/cron/cek_stok_minimum.php`
   - `0 2 * * * /usr/local/bin/php /home/USER/website-konveksi/cron/backup_db.php`

---

## 13. Roadmap Pengembangan

| Fase | Durasi | Cakupan |
|------|--------|---------|
| **1. Analisis & Desain** | 1–2 minggu | Kebutuhan, wireframe, skema DB |
| **2. Fondasi** | 1–2 minggu | Struktur MVC native, router, database, autentikasi, CSRF, layout |
| **3. MVP – Katalog & Order** | 3 minggu | Profil usaha, katalog, keranjang, custom order, upload desain, bayar manual |
| **4. Admin & Produksi** | 3 minggu | Dashboard, manajemen pesanan, tahapan produksi, mockup |
| **5. Integrasi** | 2 minggu | Email SMTP, link WhatsApp, ongkir, invoice/SPK PDF, (opsional) payment gateway |
| **6. Stok & Laporan** | 2 minggu | Bahan baku, laporan, ekspor CSV |
| **7. Pengujian & Peluncuran** | 2 minggu | UAT, perbaikan, optimasi, deploy ke hosting, pelatihan admin |
| **8. Pemeliharaan** | Berkelanjutan | Backup, pembaruan, fitur baru |

Total estimasi: **± 3–4 bulan** untuk 1–2 developer.

---

## 14. Strategi Pengujian

- **Manual/UAT** bersama pemilik dan staf (alur pesanan end-to-end).
- **Unit test** logika inti (hitung harga bertingkat, transisi status, stok) dengan PHPUnit secara lokal (tidak perlu di hosting).
- **Uji keamanan**: percobaan SQL injection, XSS, CSRF, upload file berbahaya, akses antar-pengguna.
- **Uji lintas perangkat/browser** dan uji beban ringan.

---

## 15. Risiko dan Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Batas sumber daya shared hosting | Kode ringan, pagination, cache berkas, optimasi gambar |
| Tidak ada akses SSH/Composer | Dependensi minim dan disalin manual; dokumentasikan versinya |
| Kerentanan akibat tanpa framework | Terapkan checklist keamanan bagian 10; gunakan helper terpusat; code review |
| Struktur kode berantakan | Pola MVC konsisten, konvensi penamaan, dokumentasi |
| Pelanggan terbiasa memesan via WhatsApp | Tombol WhatsApp, admin dapat menginput pesanan manual |
| File desain besar | Batas ukuran upload, validasi format, simpan di `storage/uploads` |
| Kehilangan data | Backup database harian via cron + unduhan berkala |

---

## 16. Pengembangan Lanjutan (Opsional)

- Pindah ke VPS dan framework (Laravel) bila trafik dan fitur bertambah.
- Aplikasi PWA, program loyalitas/voucher, sistem reseller.
- Integrasi marketplace dan akuntansi.
- Editor mockup interaktif di browser.
