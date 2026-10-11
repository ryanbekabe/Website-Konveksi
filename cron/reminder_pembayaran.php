<?php
// Cron cPanel: php /home/USER/website-konveksi/cron/reminder_pembayaran.php
// Membuat notifikasi untuk pesanan yang belum dibayar lebih dari 1 hari.

if (PHP_SAPI !== 'cli') {
    exit('Hanya dapat dijalankan dari CLI.');
}

define('BASE_DIR', dirname(__DIR__));
$GLOBALS['config'] = require BASE_DIR . '/config/config.php';
require BASE_DIR . '/app/core/Database.php';

$orders = Database::all(
    "SELECT o.id, o.kode_order, o.user_id FROM orders o
     WHERE o.status = 'menunggu_pembayaran' AND o.created_at < (NOW() - INTERVAL 1 DAY)
       AND NOT EXISTS (SELECT 1 FROM notifications n
                       WHERE n.user_id = o.user_id AND n.pesan LIKE CONCAT('%', o.kode_order, '%')
                         AND n.created_at > (NOW() - INTERVAL 1 DAY))"
);
foreach ($orders as $o) {
    Database::insert('notifications', [
        'user_id' => $o['user_id'],
        'pesan'   => 'Pesanan ' . $o['kode_order'] . ' menunggu pembayaran. Segera lakukan pembayaran DP.',
    ]);
}
echo count($orders) . " pengingat dibuat.\n";
