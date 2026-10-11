<?php
// Fungsi bantu global.

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape output HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    return rtrim((string) config('app.base_path', ''), '/');
}

function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** URL aset statis; ditambah ?v=waktu-ubah agar peramban memuat ulang setelah pembaruan. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $mtime = defined('PUBLIC_DIR') ? @filemtime(PUBLIC_DIR . '/assets/' . $path) : false;
    return url('assets/' . $path) . ($mtime ? '?v=' . $mtime : '');
}

function upload_url(string $subdir, ?string $file): string
{
    return $file ? url("uploads/$subdir/" . rawurlencode($file)) : asset('img/placeholder.svg');
}

/** Path permintaan tanpa query string dan tanpa base_path. */
function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = rawurldecode($path);
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim($path, '/');
    return $path;
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

function abort(int $code, string $message = '', ?string $title = null): never
{
    // Hanya kode status standar HTTP: server seperti Apache mengubah kode tak dikenal (mis. 419) menjadi 500.
    http_response_code($code);
    $titles = [400 => 'Permintaan Tidak Valid', 403 => 'Akses Ditolak', 404 => 'Halaman Tidak Ditemukan', 500 => 'Terjadi Kesalahan'];
    $title = $title ?? ($titles[$code] ?? 'Kesalahan');
    $message = $message !== '' ? $message : ($code === 404 ? 'Halaman yang Anda cari tidak ada.' : '');

    $file = BASE_DIR . '/app/views/errors/error.php';
    ob_start();
    require $file;
    $content = ob_get_clean();
    require BASE_DIR . '/app/views/layouts/main.php';
    exit;
}

/**
 * Halaman galat umum yang berdiri sendiri (tanpa layout/database) untuk exception tak tertangani.
 * Rincian hanya ditampilkan bila app.debug aktif; selain itu hanya dicatat di storage/logs/error.log.
 */
function render_fatal(?Throwable $e): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    $debug = !empty($GLOBALS['config']['app']['debug']);
    $detail = ($debug && $e) ? "\n" . get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() : '';
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Terjadi Kesalahan</title></head><body style="font-family:system-ui,sans-serif;max-width:560px;margin:15vh auto;padding:0 16px;text-align:center">'
        . '<h1>Terjadi Kesalahan</h1><p>Maaf, ada gangguan pada server. Silakan coba lagi beberapa saat lagi atau hubungi kami.</p>'
        . '<p><a href="' . htmlspecialchars(base_path() . '/', ENT_QUOTES) . '">Kembali ke beranda</a></p>'
        . ($detail !== '' ? '<pre style="text-align:left;white-space:pre-wrap;background:#f3f4f6;padding:12px;font-size:12px">' . htmlspecialchars($detail, ENT_QUOTES) . '</pre>' : '')
        . '</body></html>';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type][] = $message;
}

function flash_messages(): array
{
    return Session::pull('_flash', []);
}

/** Simpan input form agar dapat ditampilkan kembali saat validasi gagal. */
function with_old(array $input): void
{
    unset($input['password'], $input['password_confirm'], $input['_token']);
    Session::set('_old', $input);
}

function old(string $key, $default = '')
{
    static $old = null;
    if ($old === null) {
        $old = Session::pull('_old', []);
    }
    return $old[$key] ?? $default;
}

function rupiah($number): string
{
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function whatsapp_link(string $message = ''): string
{
    $number = config('app.whatsapp', '');
    return 'https://wa.me/' . $number . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

function is_active(string $prefix): string
{
    $path = request_path();
    $match = $prefix === '/' ? $path === '/' : str_starts_with($path, $prefix);
    return $match ? ' active' : '';
}

/** Kirim notifikasi dalam aplikasi ke satu pengguna. */
function notify(int $userId, string $message): void
{
    Database::insert('notifications', ['user_id' => $userId, 'pesan' => mb_substr($message, 0, 255)]);
}

/** Kirim notifikasi ke semua admin aktif. */
function notify_admins(string $message): void
{
    foreach (Database::all("SELECT id FROM users WHERE role = 'admin' AND status = 'aktif'") as $a) {
        notify((int) $a['id'], $message);
    }
}

function log_activity(string $action, string $object = '', string $detail = ''): void
{
    Database::insert('activity_logs', [
        'user_id' => Auth::id(),
        'aksi'    => $action,
        'objek'   => $object,
        'detail'  => $detail,
        'ip'      => client_ip(),
    ]);
}

function status_badge(string $status): string
{
    $class = match (true) {
        $status === 'dibatalkan' => 'badge-danger',
        $status === 'selesai' => 'badge-ok',
        in_array($status, ['menunggu_pembayaran', 'menunggu_persetujuan', 'menunggu_pelunasan'], true) => 'badge-warn',
        default => 'badge-info',
    };
    return '<span class="badge ' . $class . '">' . e(Order::label($status)) . '</span>';
}

function status_badge_stage(string $status): string
{
    $class = ['selesai' => 'badge-ok', 'berjalan' => 'badge-info', 'menunggu' => 'badge-warn'][$status] ?? 'badge-info';
    return '<span class="badge ' . $class . '">' . e(ProductionStage::STATUS[$status] ?? $status) . '</span>';
}

/** Jumlah notifikasi belum dibaca milik pengguna yang login (di-cache per permintaan). */
function unread_notifications(): int
{
    static $count = null;
    if ($count === null) {
        $count = Auth::check() ? (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND dibaca = 0', [Auth::id()]) : 0;
    }
    return $count;
}
