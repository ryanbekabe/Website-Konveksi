<?php
// Inisialisasi aplikasi: konfigurasi, error handling, autoload, session, helper.

$configFile = BASE_DIR . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Berkas config/config.php belum ada. Salin config/config.example.php menjadi config/config.php.');
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set($GLOBALS['config']['app']['timezone'] ?? 'Asia/Jakarta');
mb_internal_encoding('UTF-8');

// Error handling: di produksi jangan tampilkan error, catat ke storage/logs.
if (!empty($GLOBALS['config']['app']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_DIR . '/storage/logs/error.log');
}

// Autoload sederhana: cari kelas di folder core, controllers, models.
spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers', 'controllers/admin', 'models'] as $dir) {
        $file = BASE_DIR . "/app/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require BASE_DIR . '/app/helpers/functions.php';

Session::start();
