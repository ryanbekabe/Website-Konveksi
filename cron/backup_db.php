<?php
// Cron cPanel: php /home/USER/website-konveksi/cron/backup_db.php
// Backup database ke storage/backups/*.sql (tanpa mysqldump), simpan 14 berkas terakhir.

if (PHP_SAPI !== 'cli') {
    exit('Hanya dapat dijalankan dari CLI.');
}

define('BASE_DIR', dirname(__DIR__));
$GLOBALS['config'] = require BASE_DIR . '/config/config.php';
require BASE_DIR . '/app/core/Database.php';

$pdo = Database::pdo();
$file = BASE_DIR . '/storage/backups/backup-' . date('Ymd-His') . '.sql';
$fh = fopen($file, 'w');
fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n$create;\n\n");

    $stmt = $pdo->query("SELECT * FROM `$table`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $values = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row);
        fwrite($fh, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
    }
    fwrite($fh, "\n");
}
fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

// Hapus backup lama.
$files = glob(BASE_DIR . '/storage/backups/backup-*.sql');
sort($files);
foreach (array_slice($files, 0, max(0, count($files) - 14)) as $old) {
    unlink($old);
}
echo "Backup tersimpan: $file\n";
