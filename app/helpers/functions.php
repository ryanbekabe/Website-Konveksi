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

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
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

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $titles = [403 => 'Akses Ditolak', 404 => 'Halaman Tidak Ditemukan', 419 => 'Sesi Kedaluwarsa', 500 => 'Terjadi Kesalahan'];
    $title = $titles[$code] ?? 'Kesalahan';
    $message = $message !== '' ? $message : ($code === 404 ? 'Halaman yang Anda cari tidak ada.' : '');

    $file = BASE_DIR . '/app/views/errors/error.php';
    ob_start();
    require $file;
    $content = ob_get_clean();
    require BASE_DIR . '/app/views/layouts/main.php';
    exit;
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
