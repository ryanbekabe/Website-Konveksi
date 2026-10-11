# Rancangan Program Website Konveksi

## 1. Ringkasan Proyek

Website konveksi adalah platform web untuk bisnis produksi pakaian (kaos, kemeja, jaket, seragam, hoodie, dll.) yang menggabungkan:

1. **Company profile & katalog** – menampilkan produk, portofolio, dan informasi usaha.
2. **Sistem pemesanan online** – pelanggan dapat memesan produk custom (sablon/bordir) maupun ready stock.
3. **Manajemen produksi** – memantau status order dari desain hingga pengiriman.
4. **Dashboard admin** – mengelola produk, pesanan, pelanggan, stok bahan, dan laporan.

### Tujuan
- Mempermudah pelanggan memesan dan memantau pesanan secara online.
- Mengurangi pencatatan manual (WhatsApp/kertas) dengan sistem terpusat.
- Meningkatkan jangkauan pasar melalui SEO dan katalog digital.
- Menyediakan data penjualan dan produksi untuk pengambilan keputusan.

---

## 2. Pengguna (Aktor)

| Aktor | Deskripsi |
|-------|-----------|
| **Pengunjung** | Pengguna umum yang melihat katalog, portofolio, dan informasi. |
| **Pelanggan** | Pengunjung terdaftar yang dapat memesan, membayar, dan melacak order. |
| **Admin** | Pemilik/manajer yang mengelola seluruh sistem. |
| **Staf Produksi** | Memperbarui status tahapan produksi (potong, jahit, sablon, QC, packing). |
| **Staf Keuangan** | Memverifikasi pembayaran dan melihat laporan keuangan. |

---

## 3. Fitur Utama

### 3.1 Sisi Publik (Frontend)
- **Beranda**: hero banner, layanan unggulan, produk populer, testimoni, CTA pemesanan.
- **Katalog produk**: filter kategori, bahan, harga; pencarian; detail produk (ukuran, warna, bahan, harga bertingkat/grosir).
- **Portofolio**: galeri hasil produksi per klien/kategori.
- **Tentang kami**: profil, fasilitas, kapasitas produksi, tim.
- **Kalkulator harga (estimator)**: pilih jenis produk, bahan, jumlah, jenis sablon/bordir → estimasi harga dan waktu pengerjaan.
- **Blog/artikel**: tips seragam, bahan, tren (untuk SEO).
- **Kontak**: form, peta lokasi, tombol WhatsApp, jam operasional.
- **FAQ & syarat ketentuan**.

### 3.2 Sisi Pelanggan
- Registrasi/login (email, nomor HP, Google).
- Profil & buku alamat.
- **Pemesanan**:
  - *Ready stock*: keranjang → checkout.
  - *Custom order*: pilih produk dasar, upload desain, isi tabel ukuran (S–XXL / per ukuran), catatan, jumlah.
  - *Permintaan penawaran (RFQ)* untuk pesanan besar.
- Upload desain & **persetujuan mockup** (approve/revisi).
- **Pembayaran**: transfer bank, e-wallet, virtual account (payment gateway); dukungan DP & pelunasan.
- **Pelacakan pesanan**: timeline status + notifikasi.
- Riwayat pesanan, invoice (PDF), pesan ulang (reorder).
- Ulasan & rating produk.

### 3.3 Sisi Admin
- **Dashboard**: ringkasan penjualan, pesanan baru, order terlambat, stok menipis.
- **Manajemen produk**: kategori, varian (ukuran/warna), harga bertingkat, gambar, stok.
- **Manajemen pesanan**: daftar, detail, ubah status, cetak invoice & surat perintah kerja (SPK).
- **Manajemen produksi**: penjadwalan, penugasan staf, tahapan produksi, estimasi selesai.
- **Manajemen pembayaran**: verifikasi bukti transfer, catatan DP/pelunasan, refund.
- **Manajemen bahan baku & stok**: kain, benang, tinta, aksesori; stok masuk/keluar; peringatan minimum stok; data supplier.
- **Manajemen pelanggan**: data, riwayat order, segmentasi.
- **Konten (CMS)**: banner, portofolio, blog, FAQ, testimoni.
- **Laporan**: penjualan, produksi, keuangan, produk terlaris, pelanggan teratas (ekspor PDF/Excel).
- **Pengaturan**: tarif ongkir, metode bayar, template notifikasi, hak akses (role & permission).

### 3.4 Fitur Pendukung
- Notifikasi email & WhatsApp (konfirmasi order, status produksi, pengingat pembayaran).
- Integrasi ongkos kirim (RajaOngkir/Biteship) dan pelacakan resi.
- SEO (meta tag, sitemap, schema.org), responsif/mobile-first.
- Log aktivitas (audit trail).

---

## 4. Alur Proses Bisnis

### 4.1 Alur Pemesanan Custom
```
Pelanggan pilih produk & isi spesifikasi
        ↓
Upload desain + tabel ukuran
        ↓
Sistem hitung estimasi harga  ──►  (opsional) Admin kirim penawaran
        ↓
Pelanggan konfirmasi → bayar DP
        ↓
Admin verifikasi DP → buat mockup
        ↓
Pelanggan approve mockup (atau revisi)
        ↓
Produksi: Potong → Jahit → Sablon/Bordir → QC → Packing
        ↓
Pelunasan → Pengiriman (resi)
        ↓
Pesanan selesai → Ulasan
```

### 4.2 Status Pesanan
`Menunggu Pembayaran` → `DP Dibayar` → `Desain/Mockup` → `Menunggu Persetujuan` → `Dalam Produksi` → `QC` → `Menunggu Pelunasan` → `Siap Kirim` → `Dikirim` → `Selesai` (atau `Dibatalkan`).

### 4.3 Tahapan Produksi
`Pemotongan` → `Penjahitan` → `Sablon/Bordir` → `Finishing` → `Quality Control` → `Packing`.

---

## 5. Arsitektur Sistem

### 5.1 Rekomendasi Teknologi

| Lapisan | Pilihan Utama | Alternatif |
|---------|---------------|-----------|
| Frontend | Next.js (React) + Tailwind CSS | Vue/Nuxt, Laravel Blade |
| Backend | Laravel (PHP) atau Node.js (NestJS/Express) | Django |
| Database | MySQL / PostgreSQL | – |
| Cache/Queue | Redis | – |
| Penyimpanan file | S3-compatible (desain, mockup, bukti bayar) | Storage lokal |
| Autentikasi | JWT / session + RBAC | OAuth Google |
| Pembayaran | Midtrans / Xendit | Transfer manual |
| Notifikasi | SMTP, WhatsApp API (Fonnte/Twilio) | – |
| Deployment | Docker + VPS/Cloud, Nginx, CI/CD (GitHub Actions) | Shared hosting |

### 5.2 Diagram Arsitektur
```
 ┌──────────────┐     HTTPS      ┌──────────────────────┐
 │  Browser /   │ ─────────────► │  Frontend (Next.js)  │
 │  Mobile Web  │                └──────────┬───────────┘
 └──────────────┘                           │ REST API
                                  ┌─────────▼──────────┐
                                  │  Backend API       │
                                  │  (Auth, Order,     │
                                  │   Produksi, dll.)  │
                                  └──┬────┬─────┬──────┘
                    ┌────────────────┘    │     └───────────────┐
              ┌─────▼─────┐        ┌──────▼─────┐        ┌──────▼──────┐
              │ Database  │        │ Redis      │        │ File Storage│
              └───────────┘        └────────────┘        └─────────────┘
                                  Integrasi eksternal:
                      Payment Gateway · Ongkir · WhatsApp · Email
```

---

## 6. Rancangan Basis Data

### 6.1 Entitas Utama

| Tabel | Kolom Utama |
|-------|-------------|
| `users` | id, nama, email, no_hp, password, role, status |
| `addresses` | id, user_id, penerima, alamat, kota, provinsi, kode_pos |
| `categories` | id, nama, slug, deskripsi |
| `products` | id, category_id, nama, slug, deskripsi, bahan, harga_dasar, tipe (ready/custom), aktif |
| `product_variants` | id, product_id, ukuran, warna, sku, stok, harga |
| `price_tiers` | id, product_id, min_qty, max_qty, harga |
| `product_images` | id, product_id, url, urutan |
| `orders` | id, kode_order, user_id, tipe, status, subtotal, ongkir, diskon, total, catatan, estimasi_selesai |
| `order_items` | id, order_id, product_id, variant_id, qty, harga_satuan, spesifikasi (JSON) |
| `order_designs` | id, order_id, file_url, tipe (desain/mockup), status_approval, catatan |
| `payments` | id, order_id, jenis (DP/pelunasan), metode, jumlah, bukti_url, status, dibayar_pada |
| `shipments` | id, order_id, kurir, layanan, resi, biaya, status |
| `production_stages` | id, order_id, tahap, petugas_id, status, mulai, selesai, catatan |
| `materials` | id, nama, satuan, stok, stok_minimum, supplier_id |
| `material_movements` | id, material_id, tipe (masuk/keluar), qty, referensi_order_id, tanggal |
| `suppliers` | id, nama, kontak, alamat |
| `reviews` | id, user_id, product_id, order_id, rating, komentar |
| `portfolios` | id, judul, deskripsi, gambar, kategori |
| `posts` | id, judul, slug, konten, status, published_at |
| `notifications` | id, user_id, tipe, pesan, dibaca |
| `activity_logs` | id, user_id, aksi, objek, detail, waktu |

### 6.2 Relasi Inti
- `users` 1—N `orders`; `orders` 1—N `order_items`, `payments`, `production_stages`, `order_designs`; `orders` 1—1 `shipments`.
- `categories` 1—N `products`; `products` 1—N `product_variants`, `price_tiers`, `product_images`.
- `materials` 1—N `material_movements`; `suppliers` 1—N `materials`.

---

## 7. Rancangan API (Ringkas)

| Modul | Endpoint | Metode |
|-------|----------|--------|
| Auth | `/api/auth/register`, `/login`, `/logout`, `/me` | POST/GET |
| Produk | `/api/products`, `/api/products/{slug}` | GET |
| Kategori | `/api/categories` | GET |
| Estimasi | `/api/estimate` | POST |
| Keranjang | `/api/cart` | GET/POST/PUT/DELETE |
| Pesanan | `/api/orders`, `/api/orders/{id}` | GET/POST |
| Desain | `/api/orders/{id}/designs`, `/approve` | POST |
| Pembayaran | `/api/orders/{id}/payments`, `/api/payments/callback` | POST |
| Pengiriman | `/api/shipping/cost`, `/api/orders/{id}/tracking` | POST/GET |
| Ulasan | `/api/products/{id}/reviews` | GET/POST |
| Admin | `/api/admin/orders`, `/products`, `/materials`, `/customers`, `/reports`, `/production` | CRUD |

---

## 8. Rancangan Antarmuka (Halaman)

**Publik**: Beranda · Katalog · Detail Produk · Portofolio · Tentang · Blog · Kontak · Kalkulator Harga
**Pelanggan**: Login/Register · Keranjang · Checkout · Form Custom Order · Daftar Pesanan · Detail & Tracking Pesanan · Profil
**Admin**: Dashboard · Pesanan · Produksi (kanban per tahap) · Produk · Stok Bahan · Pembayaran · Pelanggan · Konten · Laporan · Pengguna & Hak Akses · Pengaturan

Prinsip desain: mobile-first, navigasi sederhana, tombol WhatsApp melayang, warna netral dengan aksen brand, waktu muat < 3 detik.

---

## 9. Kebutuhan Non-Fungsional

- **Keamanan**: HTTPS, hashing password (bcrypt/argon2), proteksi CSRF/XSS/SQL injection, validasi & batasan upload file, RBAC, rate limiting.
- **Performa**: caching, optimasi gambar (WebP, lazy load), pagination, CDN.
- **Ketersediaan**: backup database harian, monitoring uptime.
- **Skalabilitas**: arsitektur modular, antrian untuk tugas berat (email, laporan).
- **Kegunaan**: responsif, bahasa Indonesia (opsional multi-bahasa).
- **Kepatuhan**: kebijakan privasi, perlindungan data pribadi (UU PDP).

---

## 10. Struktur Direktori (Usulan)

```
website-konveksi/
├── frontend/            # Next.js
│   ├── app/             # halaman publik, pelanggan, admin
│   ├── components/
│   └── lib/
├── backend/             # API
│   ├── src/modules/     # auth, products, orders, production, payments, inventory, reports
│   ├── database/        # migrations & seeders
│   └── tests/
├── docs/                # dokumentasi & diagram
├── docker-compose.yml
└── README.md
```

---

## 11. Rencana Pengembangan (Roadmap)

| Fase | Durasi | Cakupan |
|------|--------|---------|
| **1. Analisis & Desain** | 1–2 minggu | Pengumpulan kebutuhan, wireframe, UI design, skema DB |
| **2. MVP – Katalog & Order** | 3–4 minggu | Company profile, katalog, auth, keranjang, custom order, upload desain, pembayaran manual |
| **3. Admin & Produksi** | 3–4 minggu | Dashboard admin, manajemen pesanan, tahapan produksi, approval mockup |
| **4. Integrasi** | 2–3 minggu | Payment gateway, ongkir, notifikasi WhatsApp/email, invoice PDF |
| **5. Stok & Laporan** | 2 minggu | Manajemen bahan baku, laporan, ekspor |
| **6. Pengujian & Peluncuran** | 2 minggu | UAT, perbaikan bug, optimasi SEO/performa, deploy, pelatihan admin |
| **7. Pemeliharaan** | Berkelanjutan | Monitoring, backup, penambahan fitur |

Total estimasi: **± 3–4 bulan** untuk tim kecil (1–2 developer, 1 desainer UI/UX).

---

## 12. Strategi Pengujian

- **Unit test**: perhitungan harga bertingkat, transisi status, stok.
- **Integration test**: alur order → pembayaran → produksi → pengiriman.
- **E2E test** (Playwright/Cypress): checkout, custom order, approval mockup.
- **UAT**: bersama pemilik dan staf konveksi.
- **Uji keamanan & beban** sebelum rilis.

---

## 13. Risiko dan Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Kebutuhan custom order sangat bervariasi | Form spesifikasi fleksibel (JSON) + fitur RFQ/penawaran manual |
| Pelanggan terbiasa memesan via WhatsApp | Tombol WhatsApp, input order manual oleh admin |
| File desain besar/format beragam | Batas ukuran, validasi format, penyimpanan object storage |
| Pembayaran manual sulit diverifikasi | Payment gateway + notifikasi otomatis |
| Staf kurang familiar dengan sistem | Antarmuka sederhana, pelatihan, panduan penggunaan |

---

## 14. Pengembangan Lanjutan (Opsional)

- Editor desain 2D/3D (mockup real-time) di browser.
- Aplikasi mobile / PWA.
- Program loyalitas, voucher, dan sistem reseller/dropship.
- Integrasi marketplace (Tokopedia/Shopee) dan akuntansi.
- Prediksi kebutuhan bahan baku berbasis data penjualan.
