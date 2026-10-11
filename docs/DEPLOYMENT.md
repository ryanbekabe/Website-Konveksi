# Panduan Deployment (Shared Hosting / cPanel)

Panduan memasang Website Konveksi di shared hosting dengan **PHP Native + MySQL**, tanpa SSH, Composer, atau Node.js.

> **Tingkat verifikasi.** Langkah dan perilaku di bawah diuji di **Apache 2.4 + mod_php (PHP 8.3) + MariaDB 10.11** dengan tiga skema penempatan (lihat bagian 2), termasuk `.htaccess`, installer, batas unggah, dan penanganan error. Diuji juga dengan `sql_mode` bawaan MySQL 8 (termasuk `ONLY_FULL_GROUP_BY`). **Belum diuji**: LiteSpeed/OpenLiteSpeed, PHP-FPM/CloudLinux, PHP 8.1 s.d. 8.2 secara khusus, dan MySQL 5.7/8 sungguhan. Bila hosting Anda memakai salah satunya, ikuti bagian *Pemecahan Masalah* dan jalankan *Uji Pasca-Deploy* (bagian 9).

## 1. Persyaratan

| Item | Minimum |
|------|---------|
| PHP | 8.1 atau lebih baru |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `fileinfo` (`gd` opsional) |
| Database | MySQL 5.7+ / MariaDB 10.3+ (utf8mb4, InnoDB) |
| Web server | Apache dengan `mod_rewrite` dan `AllowOverride All` (umumnya sudah aktif di cPanel) |
| Ruang | Beberapa MB untuk kode + ruang untuk unggahan desain dan bukti bayar |

Cek di cPanel: **Select PHP Version** (pilih 8.1+ atau 8.3 dan centang ekstensi), **MySQL Databases**, dan pastikan SSL tersedia (AutoSSL).

## 2. Memilih Penempatan Berkas

Pilih **salah satu**. Ketiganya sudah diuji.

### A. Ideal (paling aman) — isi `public/` ke `public_html/`
```
/home/USER/
├── public_html/        <- isi folder public/ (index.php, assets/, uploads/, .htaccess, .user.ini, install.php)
├── app/
├── config/
├── cron/
├── database/
├── storage/
└── vendor/
```
Hanya `public_html` yang bisa diakses web; kode dan konfigurasi berada di luar jangkauan URL.

### B. Semua di `public_html/`
Unggah seluruh isi proyek ke `public_html/` (termasuk `.htaccess` di akar). `.htaccess` akar mengarahkan semua permintaan ke `public/`, dan folder `app/`, `config/`, `database/`, `storage/`, `cron/`, `vendor/` dilindungi `.htaccess` masing-masing (`Require all denied`). Berkas di akar (README, `.git`, `composer.json`, `*.sql`, ...) juga tidak dapat diunduh. Jangan menghapus `.htaccess` mana pun.

### C. Subfolder (mis. `https://domain.com/konveksi/`)
Unggah seluruh proyek ke `public_html/konveksi/` (seperti B) lalu isi **Base path** `/konveksi` saat instalasi (atau `app.base_path` di `config/config.php`). Alamat semua tautan dan pengalihan otomatis memakai prefix itu.

## 3. Instalasi

### Opsi 1 — Installer web (disarankan; tanpa phpMyAdmin)
1. Buat database dan pengguna di **cPanel > MySQL Databases**, beri semua hak (ALL PRIVILEGES).
2. Unggah berkas sesuai bagian 2 (pakai File Manager atau FTP; ZIP lalu *Extract* lebih cepat).
3. Atur izin folder (bagian 4).
4. Buka `https://domain-anda/install.php`. Semua persyaratan harus bertanda **OK**.
5. Isi data database, data usaha, dan **akun admin dengan kata sandi pilihan Anda**, lalu klik **Pasang Aplikasi**.
6. Installer membuat 23 tabel, mengisi pengaturan awal dan kategori, membuat admin, menulis `config/config.php`, dan **menghapus dirinya sendiri**. Bila tidak berhasil dihapus, **hapus `install.php` secara manual**.

Pengaman installer: menolak berjalan bila `config/config.php` atau `storage/installed.lock` sudah ada; menolak database yang tidak kosong; memvalidasi semua isian; tidak membuat akun bawaan (`admin123` tidak ada pada instalasi lewat installer).

> Pasang segera setelah berkas diunggah. Sebelum instalasi selesai, siapa pun yang membuka `install.php` dapat memasang aplikasi.

### Opsi 2 — Manual lewat phpMyAdmin
1. Buat database; impor `database/schema.sql`, lalu `database/seed.sql`.
2. Salin `config/config.example.php` menjadi `config/config.php`; isi kredensial database, `base_path`, nama usaha, dan nomor WhatsApp; set `debug` ke `false`.
3. Hapus atau jangan unggah `install.php`.
4. Login `admin@konveksi.local` / `admin123` lalu **segera ganti kata sandi** di menu *Akun Saya* (dashboard menampilkan peringatan sampai diganti). Ubah juga data usaha di tabel `settings` (`nama_usaha`, `alamat_usaha`, `telepon_usaha`, `rekening_bank`).

## 4. Izin File dan Folder

| Target | Izin |
|--------|------|
| Folder | `755` |
| Berkas | `644` |
| `config/config.php` | `640` atau `600` (hanya pemilik akun hosting yang perlu membaca) |
| `storage/` dan isinya (`uploads`, `logs`, `cache`, `backups`), `public/uploads/products/` | dapat ditulis oleh PHP (`755`; bila gagal menyimpan file, coba `775`) |
| `config/` | dapat ditulis **hanya selama instalasi** |

Jangan memakai `777`. Pada hosting yang menjalankan PHP sebagai akun Anda (suPHP/FPM per akun), `755` sudah cukup.

## 5. Pengaturan PHP

- **Versi**: cPanel > *Select PHP Version* > pilih 8.1+ (disarankan 8.3).
- **Batas unggah**: desain dan bukti bayar hingga 5 MB. Paket `public/` sudah menyertakan:
  - `.htaccess` dengan `php_value upload_max_filesize 8M` dan `post_max_size 10M` (berlaku di mod_php/LSAPI; **diuji**).
  - `.user.ini` dengan nilai yang sama (untuk PHP-FPM/CGI; perubahan butuh beberapa menit; **belum diuji**).
  - Bila keduanya tidak berpengaruh, atur lewat cPanel > *MultiPHP INI Editor* (`upload_max_filesize` ≥ 8M, `post_max_size` ≥ 10M).
- Bila file lebih besar dari batas, pengguna melihat pesan jelas ("Ukuran file melebihi batas server" atau halaman "Sesi Kedaluwarsa ... data terlalu besar"), bukan galat 500.
- **Zona waktu**: diatur di `config/config.php` (`app.timezone`, default `Asia/Jakarta`). Sesi MySQL otomatis disamakan dengan PHP sehingga batas hari laporan dan jam yang tampil benar meskipun server MySQL memakai zona lain.

## 6. SSL / HTTPS

1. cPanel > *SSL/TLS Status* > jalankan **AutoSSL** untuk domain. Validasi memakai `/.well-known/` di akar situs; `.htaccess` proyek sudah mengecualikan folder ini dari pengalihan (diuji pada penempatan A dan B).
2. Setelah HTTPS aktif, buka `.htaccess` di folder web (`public/.htaccess` atau `public_html/.htaccess`) dan **hapus tanda `#`** pada tiga baris di bawah komentar "Paksa HTTPS".
3. Setelah yakin stabil, aktifkan juga baris `Strict-Transport-Security` (HSTS).
4. Cookie sesi otomatis diberi atribut `Secure` saat diakses lewat HTTPS.

## 7. Cron Job

cPanel > **Cron Jobs**. Ganti `USER` dan lokasi PHP sesuai hosting (lihat *Select PHP Version* atau tanya penyedia; contoh: `/usr/local/bin/php` atau `/opt/cpanel/ea-php83/root/usr/bin/php`).

```
0 8 * * * /usr/local/bin/php /home/USER/cron/reminder_pembayaran.php
0 7 * * * /usr/local/bin/php /home/USER/cron/cek_stok_minimum.php
0 2 * * * /usr/local/bin/php /home/USER/cron/backup_db.php
```
(Untuk penempatan B/C, jalur skrip: `/home/USER/public_html/cron/...`.)

- Skrip menolak dijalankan lewat web (hanya CLI).
- Uji sekali secara manual dari menu Cron dengan jadwal `* * * * *`, lihat hasilnya di email cPanel, lalu kembalikan ke jadwal semula.
- `backup_db.php` menyimpan 14 berkas terakhir di `storage/backups/` (tidak bisa diunduh lewat URL).

## 8. Backup, Pemulihan, dan Pembaruan

**Backup rutin**: unduh `storage/backups/*.sql` secara berkala (File Manager/FTP) **dan** folder unggahan: `storage/uploads/` (desain, mockup, bukti bayar) serta `public/uploads/` (gambar produk). Simpan di luar server. Backup penuh cPanel juga disarankan.

**Pemulihan database**: phpMyAdmin > pilih database > *Import* berkas `.sql` (backup berisi `DROP TABLE IF EXISTS` sehingga menimpa tabel yang ada).

**Pembaruan versi**:
1. Backup database dan folder unggahan (lihat di atas).
2. Unggah berkas baru **tanpa menimpa**: `config/config.php`, `storage/`, `public/uploads/`, dan jangan unggah ulang `install.php`.
3. Jalankan berkas di `database/migrations/` yang belum pernah dijalankan, berurutan (phpMyAdmin > *SQL*). Daftar saat ini: `001_checkout.sql` (kolom alamat kirim dan DP), `002_dokumen.sql` (pengaturan alamat/telepon usaha). Instalasi baru lewat installer/`schema.sql` sudah mencakup semuanya.
4. Buka beranda dan jalankan *Uji Pasca-Deploy*. Bila perubahan tidak tampil, tunggu beberapa detik (opcache) atau reset opcache di cPanel.
5. Rollback: kembalikan berkas lama dan impor backup database.

## 9. Uji Pasca-Deploy (±15 menit)

Centang semuanya sebelum situs diumumkan:

- [ ] Beranda, katalog, dan detail produk tampil dengan gaya (CSS) dan gambar.
- [ ] Alamat rahasia **ditolak** (harus 403/404): `/config/config.php`, `/app/routes.php`, `/database/schema.sql`, `/storage/logs/error.log`, `/.git/HEAD`, `/vendor/`.
- [ ] `https://` aktif dan `http://` dialihkan ke `https://` (setelah bagian 6).
- [ ] Login admin berhasil; kata sandi bawaan sudah diganti (tidak ada peringatan merah di dashboard).
- [ ] Pendaftaran pelanggan baru dan login/logout berjalan.
- [ ] Tambah produk dengan gambar: gambar tampil di katalog.
- [ ] Buat pesanan custom dengan desain: file desain terunggah, pesanan muncul di admin.
- [ ] Unggah bukti bayar (ukuran 2-5 MB) berhasil; verifikasi sebagai keuangan mengubah status.
- [ ] Invoice dan SPK terbuka dan tercetak/tersimpan PDF dengan benar.
- [ ] Laporan penjualan terbuka; ekspor CSV terbuka di Excel/Sheets (gunakan versi titik koma bila kolom menyatu).
- [ ] Cron berjalan (cek email cron atau file di `storage/backups/` keesokan hari).
- [ ] Jam dan tanggal pesanan sesuai zona waktu lokal.
- [ ] Buka halaman yang tidak ada: tampil halaman 404 yang rapi (bukan layar putih atau jejak kode).
- [ ] `install.php` sudah tidak ada di server.

## 10. Pemecahan Masalah

| Gejala | Penyebab umum | Solusi |
|--------|---------------|--------|
| **Error 500 di semua halaman** | Direktif `Options` di `.htaccess` dilarang oleh hosting; versi PHP < 8.1; ekstensi hilang | Lihat `storage/logs/error.log` dan *Error Log* di cPanel. Hapus baris `Options ...` di `.htaccess` (akar, `public/`, `public/uploads/`). Pilih PHP 8.1+ dan aktifkan ekstensi |
| Halaman putih / "Terjadi Kesalahan" | Galat PHP atau database (tidak ditampilkan demi keamanan) | Baca `storage/logs/error.log`. Periksa kredensial di `config/config.php`. Untuk melihat rincian sementara, set `app.debug` ke `true` lalu kembalikan ke `false` |
| Beranda tampil tetapi semua halaman lain **404** | `mod_rewrite` mati atau `AllowOverride None` | Minta hosting mengaktifkan `AllowOverride All`/`mod_rewrite`. Pastikan `.htaccess` ikut terunggah (berkas tersembunyi) |
| **403 Forbidden** di akar situs | Document root menunjuk folder yang salah | Penempatan A: isi `public/` harus langsung di `public_html/`. Penempatan B: `.htaccess` akar harus ada |
| Gaya/gambar tidak muncul, tautan salah | `base_path` tidak sesuai | Subfolder: set `/nama-folder`; root domain: kosongkan |
| "Sesi Kedaluwarsa" saat mengunggah | File melebihi `post_max_size` | Perbesar batas (bagian 5) atau kecilkan file |
| Login berputar kembali ke halaman masuk | Folder sesi PHP tidak dapat ditulis atau cookie diblokir | Pastikan PHP dapat menulis sesi (hubungi hosting); akses lewat satu domain konsisten (www vs non-www) |
| "Tidak dapat terhubung ke database" | Host/nama/pengguna salah (cPanel menambah awalan akun, mis. `akun_namadb`) | Salin nama persis dari *MySQL Databases*; host umumnya `localhost` |
| Gambar produk gagal diunggah | `public/uploads/products` tidak dapat ditulis | Atur izin 755/775 |
| Tanggal/jam laporan meleset | `app.timezone` salah | Sesuaikan di `config/config.php` |
| Cron tidak berjalan | Jalur PHP/skrip salah | Pakai jalur absolut; uji manual (bagian 7) |
| CSV menyatu dalam satu kolom di Excel | Excel berlokal Indonesia memakai `;` | Unduh versi "titik koma" |

## 11. Daftar Periksa Keamanan

- [ ] Kata sandi admin kuat; tidak memakai `admin123`.
- [ ] `app.debug` bernilai `false` di produksi.
- [ ] `install.php` terhapus; `config/config.php` berizin 640/600 dan tidak ikut di-commit.
- [ ] HTTPS aktif dan dipaksa.
- [ ] Backup rutin disimpan di luar server.
- [ ] Akun staf yang tidak dipakai dinonaktifkan di menu Pengguna.
- [ ] PHP dan hosting diperbarui; hanya ekstensi yang diperlukan aktif.
- [ ] Folder `storage/`, `app/`, `config/`, `database/`, `cron/`, `vendor/` tidak dapat diakses lewat URL (cek bagian 9).

## 12. Menjalankan Uji Otomatis (Pengembang)

```bash
bash tests/run.sh         # sintaks PHP + uji unit/integritas + uji end-to-end
php tests/unit.php        # hanya uji unit dan integritas (tanpa database)
bash tests/e2e.sh         # hanya end-to-end (butuh MySQL; membuat dan menghapus database sementara)
```
Variabel: `DB_HOST`, `DB_USER`, `DB_PASS`, `PORT`, `SKIP_E2E=1`. Pengguna MySQL harus boleh `CREATE`/`DROP DATABASE`; **jangan** diarahkan ke database produksi (uji memakai database sementara bernama `konveksi_e2e_*`).

Cakupan sekitar 1.000 pemeriksaan: helper dan validasi, pencegahan CSV injection, integritas rute (kelas/metode ada, parameter cocok, rute statis tidak tertutup rute berparameter), matriks hak akses 5 peran x 15 halaman, siklus pesanan lengkap (pembayaran, mockup, produksi, pengiriman), IDOR antar pelanggan, XSS, unggahan berbahaya, stok termasuk mutasi serentak, laporan (angka dicocokkan dengan SQL), dan manajemen pengguna.

Menguji `.htaccess` secara lokal membutuhkan Apache + mod_php + `mod_rewrite`/`mod_headers`; server bawaan PHP (`php -S`) **tidak** membaca `.htaccess`.
