-- Migrasi 002: pengaturan alamat & telepon usaha untuk invoice dan SPK.
-- Aman dijalankan berulang. Ubah nilainya sesuai data usaha Anda.
INSERT IGNORE INTO settings (`key`, `value`) VALUES
('alamat_usaha', 'Jl. Contoh No. 1, Bandung, Jawa Barat'),
('telepon_usaha', '081234567890');
