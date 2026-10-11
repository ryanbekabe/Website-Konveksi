<?php
// Cron cPanel: php /home/USER/website-konveksi/cron/cek_stok_minimum.php
// Mencatat bahan baku yang stoknya di bawah batas minimum ke notifikasi admin.

if (PHP_SAPI !== 'cli') {
    exit('Hanya dapat dijalankan dari CLI.');
}

define('BASE_DIR', dirname(__DIR__));
$GLOBALS['config'] = require BASE_DIR . '/config/config.php';
require BASE_DIR . '/app/core/Database.php';

$low = Database::all('SELECT nama, stok, satuan FROM materials WHERE stok <= stok_minimum');
if (!$low) {
    exit(0);
}

$admins = Database::all("SELECT id FROM users WHERE role = 'admin' AND status = 'aktif'");
foreach ($low as $m) {
    $pesan = sprintf('Stok %s menipis: %s %s', $m['nama'], rtrim(rtrim((string) $m['stok'], '0'), '.'), $m['satuan']);
    foreach ($admins as $a) {
        // Lewati bila pengingat yang sama sudah ada dan belum dibaca (hindari menumpuk tiap hari).
        $sudah = Database::value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND dibaca = 0 AND pesan LIKE ?',
            [$a['id'], 'Stok ' . $m['nama'] . ' menipis%']
        );
        if (!$sudah) {
            Database::insert('notifications', ['user_id' => $a['id'], 'pesan' => mb_substr($pesan, 0, 255)]);
        }
    }
}
echo count($low) . " bahan menipis dicatat.\n";
