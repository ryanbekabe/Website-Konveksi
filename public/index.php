<?php
// Front controller: semua permintaan masuk lewat berkas ini.

define('BASE_DIR', dirname(__DIR__));

require BASE_DIR . '/app/core/bootstrap.php';

$router = new Router();
require BASE_DIR . '/app/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], request_path());
