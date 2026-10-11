-- Data awal. Impor setelah schema.sql.
-- AKUN ADMIN BAWAAN: admin@konveksi.local / admin123
-- WAJIB diganti kata sandinya setelah login pertama.

SET NAMES utf8mb4;

INSERT INTO users (nama, email, no_hp, password_hash, role) VALUES
('Administrator', 'admin@konveksi.local', '081234567890', '$2y$10$AL6fXW8XRILl30lIt0FRLufWX7MI/um5cAmSYLJVxgH3rvjKnzDQi', 'admin');

INSERT INTO categories (nama, slug, deskripsi) VALUES
('Kaos', 'kaos', 'Kaos polos dan sablon'),
('Kemeja', 'kemeja', 'Kemeja seragam dan casual'),
('Jaket & Hoodie', 'jaket-hoodie', 'Jaket, hoodie, dan sweater'),
('Seragam', 'seragam', 'Seragam kerja, sekolah, dan komunitas');

INSERT INTO products (category_id, nama, slug, deskripsi, bahan, harga_dasar, tipe, aktif) VALUES
(1, 'Kaos Cotton Combed 30s', 'kaos-cotton-combed-30s', 'Kaos lembut dan adem, cocok untuk sablon DTF maupun plastisol.', 'Cotton Combed 30s', 55000, 'custom', 1),
(2, 'Kemeja Seragam Kerja', 'kemeja-seragam-kerja', 'Kemeja seragam dengan bordir logo perusahaan.', 'Katun Toyobo', 125000, 'custom', 1),
(3, 'Hoodie Fleece Premium', 'hoodie-fleece-premium', 'Hoodie tebal dengan bahan fleece, tersedia berbagai warna.', 'Fleece Cotton', 165000, 'custom', 1);

INSERT INTO price_tiers (product_id, min_qty, max_qty, harga) VALUES
(1, 1, 11, 55000),
(1, 12, 49, 48000),
(1, 50, NULL, 42000);

INSERT INTO product_variants (product_id, ukuran, warna, sku, stok) VALUES
(1, 'S', 'Hitam', 'KAOS-S-HTM', 0), (1, 'M', 'Hitam', 'KAOS-M-HTM', 0),
(1, 'L', 'Hitam', 'KAOS-L-HTM', 0), (1, 'XL', 'Hitam', 'KAOS-XL-HTM', 0),
(1, 'S', 'Putih', 'KAOS-S-PTH', 0), (1, 'M', 'Putih', 'KAOS-M-PTH', 0),
(1, 'L', 'Putih', 'KAOS-L-PTH', 0), (1, 'XL', 'Putih', 'KAOS-XL-PTH', 0);

INSERT INTO settings (`key`, `value`) VALUES
('nama_usaha', 'Konveksi Maju Jaya'),
('rekening_bank', 'BCA 1234567890 a.n. Konveksi Maju Jaya'),
('dp_persen', '50');
