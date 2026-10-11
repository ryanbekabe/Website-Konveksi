<?php
// Front controller: semua permintaan masuk lewat berkas ini.

// Server bawaan PHP (php -S): biarkan berkas statis (css, js, gambar) disajikan langsung.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_file($file) && realpath($file) !== __FILE__ && !str_ends_with($file, '.php')) {
        return false;
    }
}

define('BASE_DIR', dirname(__DIR__));

require BASE_DIR . '/app/core/bootstrap.php';

$router = new Router();
require BASE_DIR . '/app/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], request_path());
