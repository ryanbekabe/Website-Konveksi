<?php
// Salin berkas ini menjadi config/config.php lalu sesuaikan nilainya.
// config.php tidak boleh di-commit.

return [
    'app' => [
        'name'      => 'Konveksi Maju Jaya',
        // Kosongkan bila di root domain; isi '/konveksi' bila di subfolder (tanpa slash di akhir).
        'base_path' => '',
        'debug'     => false,   // true hanya saat pengembangan
        'timezone'  => 'Asia/Jakarta',
        'whatsapp'  => '6281234567890', // format internasional tanpa +
    ],
    'db' => [
        'host'    => 'localhost',
        'name'    => 'konveksi',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'upload' => [
        'max_size'   => 2 * 1024 * 1024, // 2 MB
        'image_mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    ],
    'smtp' => [
        'host' => 'mail.example.com',
        'port' => 587,
        'user' => 'noreply@example.com',
        'pass' => '',
        'from' => 'noreply@example.com',
    ],
];
