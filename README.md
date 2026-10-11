# Website-Konveksi

Aplikasi web untuk bisnis konveksi (kaos, kemeja, jaket, seragam, hoodie, dll.), dirancang dengan **PHP Native** dan **MySQL** agar dapat berjalan di **shared hosting** (cPanel) tanpa SSH, Node.js, atau Docker.

- **Company profile & katalog** produk dan portofolio
- **Pemesanan online** untuk produk ready stock maupun custom (sablon/bordir)
- **Manajemen produksi**: pelacakan status dari desain hingga pengiriman
- **Panel admin**: produk, pesanan, pembayaran, stok bahan, pelanggan, dan laporan

## Status

Tahap perancangan. Belum ada kode aplikasi.

## Dokumentasi

- [Rancangan Program](RANCANGAN_PROGRAM.md): fitur, alur bisnis, arsitektur MVC native, struktur direktori, rancangan database dan rute, keamanan, panduan deployment shared hosting, roadmap, pengujian, dan risiko.

## Teknologi

PHP Native (≥ 7.4) dengan pola MVC sederhana, MySQL/MariaDB (PDO), Apache `.htaccess`, Bootstrap/JavaScript vanilla, PHPMailer, dan Cron Job cPanel untuk tugas terjadwal.
