-- Migrasi 001: kolom alamat kirim & nominal DP pada pesanan.
-- Jalankan HANYA bila database dibuat dari schema.sql versi sebelum checkout.
-- (schema.sql terbaru sudah memuat kolom ini.)
ALTER TABLE orders
    ADD COLUMN alamat_kirim TEXT NULL AFTER catatan,
    ADD COLUMN dp_jumlah DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total;
