<?php
/**
 * Installer web sekali pakai untuk shared hosting tanpa SSH.
 *
 * Yang dikerjakan: memeriksa persyaratan, membuat tabel dari database/schema.sql, mengisi data awal,
 * membuat akun admin dengan kata sandi pilihan Anda (tanpa akun bawaan), menulis config/config.php,
 * lalu mengunci diri dengan storage/installed.lock.
 *
 * KEAMANAN: HAPUS berkas ini dari server segera setelah pemasangan selesai.
 * Berkas ini menolak berjalan bila config/config.php atau storage/installed.lock sudah ada.
 */

declare(strict_types=1);

const MIN_PHP = '8.1.0';
$publicDir = __DIR__;
$baseDir = dirname(__DIR__);
$configFile = $baseDir . '/config/config.php';
$lockFile = $baseDir . '/storage/installed.lock';

session_name('konveksi_install');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function h($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function page(string $title, string $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>' . h($title) . '</title><style>'
        . 'body{font-family:system-ui,sans-serif;max-width:640px;margin:24px auto;padding:0 16px;color:#111;line-height:1.5}'
        . 'h1{font-size:1.5rem}fieldset{border:1px solid #ddd;border-radius:8px;margin:16px 0;padding:8px 16px 16px}'
        . 'legend{font-weight:600;padding:0 6px}label{display:block;margin-top:10px;font-weight:500}'
        . 'input[type=text],input[type=password],input[type=email]{width:100%;padding:8px;border:1px solid #bbb;border-radius:6px;box-sizing:border-box}'
        . 'button{background:#1d4ed8;color:#fff;border:0;padding:10px 20px;border-radius:6px;font-size:1rem;cursor:pointer}'
        . '.ok{color:#15803d}.bad{color:#b91c1c;font-weight:600}.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:6px;margin:12px 0}'
        . '.warn{background:#fffbeb;border:1px solid #fde68a;padding:10px 14px;border-radius:6px;margin:12px 0}.muted{color:#666;font-size:.9rem}table{border-collapse:collapse;width:100%}td{padding:4px 8px;border-bottom:1px solid #eee}'
        . '</style></head><body>' . $body . '</body></html>';
    exit;
}

// 1) Kunci: jangan pernah berjalan bila sudah terpasang.
if (is_file($configFile) || is_file($lockFile)) {
    page('Sudah Terpasang', '<h1>Aplikasi sudah terpasang</h1><p>Demi keamanan, installer dinonaktifkan. '
        . '<strong>Hapus berkas <code>install.php</code></strong> dari server.</p>', 403);
}

// 2) Persyaratan. Folder kosong sering hilang saat ZIP diekstrak, jadi coba buat bila belum ada.
foreach (['storage/uploads/designs', 'storage/uploads/payments', 'storage/logs', 'storage/cache', 'storage/backups'] as $rel) {
    if (!is_dir("$baseDir/$rel")) {
        @mkdir("$baseDir/$rel", 0755, true);
    }
}
if (!is_dir($publicDir . '/uploads/products')) {
    @mkdir($publicDir . '/uploads/products', 0755, true);
}
$checks = [];
$checks[] = ['PHP ≥ ' . MIN_PHP . ' (terpasang ' . PHP_VERSION . ')', version_compare(PHP_VERSION, MIN_PHP, '>='), true];
foreach (['pdo_mysql' => 'Ekstensi pdo_mysql', 'mbstring' => 'Ekstensi mbstring', 'fileinfo' => 'Ekstensi fileinfo'] as $ext => $label) {
    $checks[] = [$label, extension_loaded($ext), true];
}
$checks[] = ['Ekstensi gd (opsional)', extension_loaded('gd'), false];
$writable = [
    'config/'                 => $baseDir . '/config',
    'storage/'                => $baseDir . '/storage',
    'storage/uploads/'        => $baseDir . '/storage/uploads',
    'storage/logs/'           => $baseDir . '/storage/logs',
    'public/uploads/products/' => $publicDir . '/uploads/products',
];
foreach ($writable as $label => $path) {
    $checks[] = ["Folder $label dapat ditulis", is_dir($path) && is_writable($path), true];
}
$checks[] = ['Berkas database/schema.sql tersedia', is_file($baseDir . '/database/schema.sql'), true];
$checks[] = ['Berkas config/config.example.php tersedia', is_file($baseDir . '/config/config.example.php'), true];
$ready = !array_filter($checks, fn($c) => $c[2] && !$c[1]);

// Perkiraan base_path dari URL (subfolder), tanpa slash di akhir.
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/install.php', PHP_URL_PATH) ?: '/install.php';
$guessBase = rtrim(str_replace('\\', '/', dirname($uriPath)), '/');
if ($guessBase === '.' || $guessBase === '/') {
    $guessBase = '';
}

if (empty($_SESSION['t'])) {
    $_SESSION['t'] = bin2hex(random_bytes(32));
}

$errors = [];
$in = [
    'db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'app_name' => 'Konveksi Anda', 'base_path' => $guessBase, 'whatsapp' => '', 'alamat' => '', 'telepon' => '',
    'admin_nama' => '', 'admin_email' => '', 'sample' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($in as $k => $_) {
        if (isset($_POST[$k]) && is_string($_POST[$k])) {
            $in[$k] = trim($_POST[$k]);
        }
    }
    $in['sample'] = isset($_POST['sample']) ? '1' : '';
    $pass1 = (string) ($_POST['admin_pass'] ?? '');
    $pass2 = (string) ($_POST['admin_pass2'] ?? '');

    if (!hash_equals($_SESSION['t'], (string) ($_POST['t'] ?? ''))) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman.';
    }
    if (!$ready) {
        $errors[] = 'Persyaratan belum terpenuhi.';
    }
    if ($in['db_name'] === '' || !preg_match('/^[A-Za-z0-9_$\-]+$/', $in['db_name'])) {
        $errors[] = 'Nama database wajib diisi (huruf, angka, _ - $).';
    }
    if ($in['db_user'] === '') {
        $errors[] = 'Pengguna database wajib diisi.';
    }
    if (!preg_match('/^[A-Za-z0-9.\-:_]+$/', $in['db_host'])) {
        $errors[] = 'Host database tidak valid.';
    }
    if ($in['app_name'] === '' || mb_strlen($in['app_name']) > 100) {
        $errors[] = 'Nama usaha wajib diisi (maks. 100 karakter).';
    }
    if ($in['base_path'] !== '' && !preg_match('#^/[A-Za-z0-9._\-/]*$#', $in['base_path'])) {
        $errors[] = 'Base path harus kosong atau diawali "/" (contoh: /konveksi).';
    }
    if ($in['whatsapp'] !== '' && !preg_match('/^[0-9]{8,16}$/', $in['whatsapp'])) {
        $errors[] = 'Nomor WhatsApp hanya angka dengan kode negara, tanpa + (contoh: 6281234567890).';
    }
    if ($in['admin_nama'] === '' || mb_strlen($in['admin_nama']) > 100) {
        $errors[] = 'Nama admin wajib diisi.';
    }
    if (!filter_var($in['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email admin tidak valid.';
    }
    if (mb_strlen($pass1) < 8 || mb_strlen($pass1) > 72) {
        $errors[] = 'Kata sandi admin harus 8 sampai 72 karakter.';
    } elseif ($pass1 !== $pass2) {
        $errors[] = 'Konfirmasi kata sandi admin tidak sama.';
    }

    if (!$errors) {
        try {
            $pdo = new PDO(
                "mysql:host={$in['db_host']};dbname={$in['db_name']};charset=utf8mb4",
                $in['db_user'], (string) ($_POST['db_pass'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            $errors[] = 'Tidak dapat terhubung ke database: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            if ($pdo->query('SHOW TABLES')->fetch()) {
                throw new RuntimeException('Database tidak kosong. Gunakan database baru yang masih kosong agar data tidak tertimpa.');
            }

            // Jalankan schema.sql: buang komentar, pecah per pernyataan.
            $sql = (string) file_get_contents($baseDir . '/database/schema.sql');
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
                $pdo->exec($stmt);
            }

            $pdo->beginTransaction();
            $ins = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)');
            foreach ([
                'nama_usaha' => $in['app_name'], 'rekening_bank' => 'Isi rekening di tabel settings (rekening_bank)',
                'dp_persen' => '50', 'alamat_usaha' => $in['alamat'], 'telepon_usaha' => $in['telepon'],
            ] as $k => $v) {
                $ins->execute([$k, $v]);
            }
            $pdo->prepare("INSERT INTO users (nama, email, no_hp, password_hash, role) VALUES (?, ?, '', ?, 'admin')")
                ->execute([$in['admin_nama'], strtolower($in['admin_email']), password_hash($pass1, PASSWORD_DEFAULT)]);

            $cat = $pdo->prepare('INSERT INTO categories (nama, slug, deskripsi) VALUES (?, ?, ?)');
            foreach ([['Kaos', 'kaos', 'Kaos polos dan sablon'], ['Kemeja', 'kemeja', 'Kemeja seragam dan casual'],
                      ['Jaket & Hoodie', 'jaket-hoodie', 'Jaket, hoodie, dan sweater'], ['Seragam', 'seragam', 'Seragam kerja, sekolah, dan komunitas']] as $c) {
                $cat->execute($c);
            }
            if ($in['sample'] === '1') {
                $prod = $pdo->prepare("INSERT INTO products (category_id, nama, slug, deskripsi, bahan, harga_dasar, tipe, aktif) VALUES (?, ?, ?, ?, ?, ?, 'custom', 1)");
                $prod->execute([1, 'Kaos Cotton Combed 30s', 'kaos-cotton-combed-30s', 'Kaos lembut dan adem, cocok untuk sablon.', 'Cotton Combed 30s', 55000]);
                $pid = (int) $pdo->lastInsertId();
                $tier = $pdo->prepare('INSERT INTO price_tiers (product_id, min_qty, max_qty, harga) VALUES (?, ?, ?, ?)');
                foreach ([[1, 11, 55000], [12, 49, 48000], [50, null, 42000]] as $t) {
                    $tier->execute([$pid, $t[0], $t[1], $t[2]]);
                }
            }
            $pdo->commit();

            // Tulis config.php dari contoh (komentar tidak dipertahankan).
            $cfg = require $baseDir . '/config/config.example.php';
            $cfg['app']['name'] = $in['app_name'];
            $cfg['app']['base_path'] = rtrim($in['base_path'], '/');
            $cfg['app']['debug'] = false;
            $cfg['app']['whatsapp'] = $in['whatsapp'];
            $cfg['db'] = ['host' => $in['db_host'], 'name' => $in['db_name'], 'user' => $in['db_user'],
                          'pass' => (string) ($_POST['db_pass'] ?? ''), 'charset' => 'utf8mb4'];
            $php = "<?php\n// Dibuat oleh install.php pada " . date('Y-m-d H:i:s') . ". Jangan di-commit.\n\nreturn " . var_export($cfg, true) . ";\n";
            if (file_put_contents($configFile, $php, LOCK_EX) === false) {
                throw new RuntimeException('Tidak dapat menulis config/config.php (periksa izin folder config/).');
            }
            @chmod($configFile, 0640);
            @file_put_contents($lockFile, 'installed ' . date('c') . "\n");

            $deleted = @unlink(__FILE__);
            $base = rtrim($in['base_path'], '/');
            page('Pemasangan Selesai', '<h1 class="ok">Pemasangan selesai</h1>'
                . '<p>Akun admin: <strong>' . h(strtolower($in['admin_email'])) . '</strong></p>'
                . ($deleted
                    ? '<p class="ok">install.php sudah dihapus otomatis.</p>'
                    : '<div class="warn"><strong>Penting:</strong> hapus <code>install.php</code> secara manual lewat File Manager sekarang.</div>')
                . '<ol><li>Buka <a href="' . h($base) . '/masuk">halaman masuk</a> dan login sebagai admin.</li>'
                . '<li>Lengkapi data usaha dan nomor rekening di tabel <code>settings</code> (phpMyAdmin): <code>rekening_bank</code>.</li>'
                . '<li>Atur Cron Job dan SSL sesuai panduan <code>docs/DEPLOYMENT.md</code>.</li></ol>');
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Gagal memasang: ' . $e->getMessage()
                . ' Bila tabel sebagian terbentuk, kosongkan database (hapus semua tabel) sebelum mencoba lagi.';
        }
    }
}

$rows = '';
foreach ($checks as [$label, $okay, $required]) {
    $rows .= '<tr><td>' . h($label) . '</td><td class="' . ($okay ? 'ok' : ($required ? 'bad' : 'muted')) . '">'
        . ($okay ? 'OK' : ($required ? 'GAGAL' : 'tidak ada')) . '</td></tr>';
}
$err = $errors ? '<div class="err"><ul style="margin:0;padding-left:18px"><li>' . implode('</li><li>', array_map('h', $errors)) . '</li></ul></div>' : '';

$form = '<form method="post" autocomplete="off"><input type="hidden" name="t" value="' . h($_SESSION['t']) . '">'
    . '<fieldset><legend>Database MySQL (buat dulu di cPanel &gt; MySQL Databases)</legend>'
    . '<label>Host<input type="text" name="db_host" value="' . h($in['db_host']) . '" required></label>'
    . '<label>Nama database<input type="text" name="db_name" value="' . h($in['db_name']) . '" required></label>'
    . '<label>Pengguna<input type="text" name="db_user" value="' . h($in['db_user']) . '" required></label>'
    . '<label>Kata sandi<input type="password" name="db_pass" autocomplete="new-password"></label></fieldset>'
    . '<fieldset><legend>Usaha</legend>'
    . '<label>Nama usaha<input type="text" name="app_name" value="' . h($in['app_name']) . '" required></label>'
    . '<label>Nomor WhatsApp (6281234567890)<input type="text" name="whatsapp" value="' . h($in['whatsapp']) . '"></label>'
    . '<label>Telepon<input type="text" name="telepon" value="' . h($in['telepon']) . '"></label>'
    . '<label>Alamat<input type="text" name="alamat" value="' . h($in['alamat']) . '"></label>'
    . '<label>Base path (kosong bila di root domain, mis. /konveksi bila di subfolder)<input type="text" name="base_path" value="' . h($in['base_path']) . '"></label></fieldset>'
    . '<fieldset><legend>Akun admin</legend>'
    . '<label>Nama<input type="text" name="admin_nama" value="' . h($in['admin_nama']) . '" required></label>'
    . '<label>Email (untuk login)<input type="email" name="admin_email" value="' . h($in['admin_email']) . '" required></label>'
    . '<label>Kata sandi (min. 8 karakter)<input type="password" name="admin_pass" minlength="8" maxlength="72" required autocomplete="new-password"></label>'
    . '<label>Ulangi kata sandi<input type="password" name="admin_pass2" minlength="8" maxlength="72" required autocomplete="new-password"></label></fieldset>'
    . '<label><input type="checkbox" name="sample" value="1" ' . ($in['sample'] === '1' ? 'checked' : '') . '> Sertakan satu produk contoh</label>'
    . '<p><button type="submit"' . ($ready ? '' : ' disabled') . '>Pasang Aplikasi</button></p></form>';

page('Pasang Aplikasi', '<h1>Pasang Website Konveksi</h1>'
    . '<div class="warn">Hapus <code>install.php</code> setelah selesai. Jangan biarkan berkas ini di server.</div>'
    . '<h2>Persyaratan</h2><table>' . $rows . '</table>' . $err . ($ready ? '' : '<p class="bad">Perbaiki persyaratan bertanda GAGAL, lalu muat ulang halaman.</p>') . $form);
