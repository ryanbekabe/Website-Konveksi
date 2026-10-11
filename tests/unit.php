<?php
/**
 * Uji unit dan pemeriksaan integritas tanpa database dan tanpa pustaka tambahan.
 * Jalankan: php tests/unit.php   (exit code 0 = semua lulus)
 */

define('BASE_DIR', dirname(__DIR__));
define('PUBLIC_DIR', BASE_DIR . '/public');
$GLOBALS['config'] = [
    'app' => ['name' => 'Uji', 'base_path' => '', 'timezone' => 'Asia/Jakarta', 'debug' => true, 'whatsapp' => '6281'],
    'db' => [], 'upload' => ['max_size' => 1048576, 'image_mime' => ['image/png' => 'png']],
];
date_default_timezone_set('Asia/Jakarta');
mb_internal_encoding('UTF-8');

spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers', 'controllers/admin', 'models'] as $dir) {
        $f = BASE_DIR . "/app/$dir/$class.php";
        if (is_file($f)) {
            require $f;
            return;
        }
    }
});
require BASE_DIR . '/app/helpers/functions.php';

$pass = 0;
$fail = [];
function check(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
    } else {
        $fail[] = $name . ($detail !== '' ? " ($detail)" : '');
        echo "  GAGAL: $name" . ($detail !== '' ? " ($detail)" : '') . "\n";
    }
}
function same(string $name, $expected, $actual): void
{
    check($name, $expected === $actual, 'harapan ' . var_export($expected, true) . ', hasil ' . var_export($actual, true));
}

echo "== Helper\n";
same('slugify dasar', 'kaos-cotton-combed-30s', slugify('Kaos Cotton Combed 30s'));
same('slugify simbol', 'jaket-hoodie', slugify('  Jaket & Hoodie!! '));
same('slugify kosong', 'item', slugify('!!!'));
same('rupiah', 'Rp 1.234.500', rupiah(1234500));
same('rupiah desimal dibulatkan', 'Rp 1.000', rupiah('999.6'));
same('e() escape', '&lt;script&gt;&quot;x&#039;', e('<script>"x\''));
same('url() tanpa base', '/produk', url('produk'));
$GLOBALS['config']['app']['base_path'] = '/konveksi/';
same('url() dengan base', '/konveksi/produk', url('/produk'));
same('asset() tanpa berkas -> tanpa versi', '/konveksi/assets/zzz.css', asset('zzz.css'));
check('asset() memberi ?v= pada berkas ada', (bool) preg_match('#^/konveksi/assets/css/style\.css\?v=\d+$#', asset('css/style.css')));
$_SERVER['REQUEST_URI'] = '/konveksi/produk/kaos?x=1';
same('request_path buang base & query', '/produk/kaos', request_path());
$_SERVER['REQUEST_URI'] = '/konveksi';
same('request_path akar', '/', request_path());
$GLOBALS['config']['app']['base_path'] = '';

echo "== Validator\n";
$v = new Validator(['email' => 'x', 'nama' => '', 'hp' => 'abc', 'role' => 'z'], ['email' => 'required|email', 'nama' => 'required', 'hp' => 'phone', 'role' => 'in:a,b']);
check('validator mendeteksi 4 galat', count($v->errors()) === 4, json_encode($v->errors()));
$v = new Validator(['email' => 'a@b.co', 'nama' => 'Budi', 'hp' => '0812345678', 'role' => 'a', 'x' => ''], ['email' => 'required|email', 'nama' => 'required|max:10', 'hp' => 'phone', 'role' => 'in:a,b', 'x' => 'email|min:5']);
check('validator lolos & isian kosong opsional dilewati', !$v->fails(), json_encode($v->errors()));
check('validator max/min', (new Validator(['n' => str_repeat('a', 11)], ['n' => 'max:10']))->fails() && (new Validator(['n' => 'ab'], ['n' => 'min:3']))->fails());

echo "== Csv (pencegahan CSV injection)\n";
same('rumus = diberi petik', "'=1+1", Csv::cell('=1+1'));
same('+ diberi petik', "'+62812", Csv::cell('+62812'));
same('- diberi petik', "'-cmd", Csv::cell('-cmd'));
same('@ diberi petik', "'@SUM(A1)", Csv::cell('@SUM(A1)'));
same('tab diberi petik', "'\tx", Csv::cell("\tx"));
same('teks biasa tetap', 'Budi', Csv::cell('Budi'));
same('angka negatif tetap numerik', -5.5, Csv::cell(-5.5));
same('int tetap', 12, Csv::cell(12));
same('kosong tetap', '', Csv::cell(''));

echo "== Report::range\n";
$r = Report::range('2026-10-01', '2026-10-11');
same('range dari/sampai', ['2026-10-01', '2026-10-11', 11], [$r['dari'], $r['sampai'], $r['days']]);
same('range akhir eksklusif', '2026-10-12 00:00:00', $r['end']);
$r = Report::range('2026-10-11', '2026-10-01');
same('range terbalik ditukar', ['2026-10-01', '2026-10-11'], [$r['dari'], $r['sampai']]);
$r = Report::range('abc', 'zzz');
same('range invalid -> bulan berjalan', [date('Y-m-01'), date('Y-m-d')], [$r['dari'], $r['sampai']]);
$r = Report::range('2020-01-01', '2026-10-11');
check('range > 366 hari dibatasi', $r['clamped'] && $r['days'] === Report::MAX_DAYS && $r['dari'] === '2025-10-11', json_encode($r));
$r = Report::range('2026-02-30', '2026-03-05');
check('tanggal mustahil (30 Feb) ditolak', !in_array('2026-02-30', [$r['dari'], $r['sampai']], true));

echo "== Material::fmt\n";
same('fmt bulat', '12', Material::fmt(12.0));
same('fmt desimal', '12,5', Material::fmt(12.5));
same('fmt ribuan', '1.250,25', Material::fmt(1250.25));
same('fmt nol', '0', Material::fmt(0.0));

echo "== Status pesanan\n";
foreach (Order::FLOW as $s) {
    check("FLOW '$s' ada di STATUS", isset(Order::STATUS[$s]));
}
foreach (array_keys(Order::STATUS) as $s) {
    foreach (Order::allowedNext($s) as $t) {
        check("transisi $s -> $t menuju status yang ada", isset(Order::STATUS[$t]));
    }
}
check('status akhir tidak punya transisi manual', Order::allowedNext('selesai') === [] && Order::allowedNext('dibatalkan') === []);
check('STAGES punya label', count(array_diff(Order::STAGES, array_keys(Order::STAGE_LABELS))) === 0);
same('label status dikenal', 'Dalam produksi', Order::label('produksi'));
same('label tahap', 'Quality Control', Order::stageLabel('qc'));
check('dibatalkan hanya dari tahap awal', !in_array('dibatalkan', Order::allowedNext('produksi'), true) && in_array('dibatalkan', Order::allowedNext('menunggu_pembayaran'), true));
check('badge status ber-escape', str_contains(status_badge('produksi'), 'badge'));

echo "== Integritas rute\n";
$routes = [];
preg_match_all('/\$router->(get|post)\(\s*\'([^\']+)\'\s*,\s*\'([A-Za-z]+)@([A-Za-z]+)\'\s*(?:,\s*\[([^\]]*)\])?\s*\)/', file_get_contents(BASE_DIR . '/app/routes.php'), $m, PREG_SET_ORDER);
check('rute terbaca', count($m) > 60, 'jumlah ' . count($m));
$seen = [];
$roles = array_keys(User::ROLES);
foreach ($m as $i => $parts) {
    [$all, $verb, $path, $class, $method] = $parts;
    $mwRaw = $parts[5] ?? '';
    $key = strtoupper($verb) . ' ' . $path;
    check("rute unik: $key", !isset($seen[$key]));
    $seen[$key] = true;
    check("kelas $class ada ($key)", class_exists($class));
    if (class_exists($class)) {
        check("$class::$method publik ($key)", method_exists($class, $method) && (new ReflectionMethod($class, $method))->isPublic());
        check("$class turunan Controller", is_subclass_of($class, 'Controller'));
        // Jumlah parameter metode harus sama dengan jumlah {placeholder} pada rute.
        if (method_exists($class, $method)) {
            $nParams = preg_match_all('/\{\w+\}/', $path);
            check("parameter $class::$method = $nParams", (new ReflectionMethod($class, $method))->getNumberOfParameters() === $nParams);
        }
    }
    preg_match_all("/'([^']+)'/", $mwRaw, $mwm);
    foreach ($mwm[1] as $mw) {
        if (str_starts_with($mw, 'role:')) {
            $bad = array_diff(explode(',', substr($mw, 5)), $roles);
            check("peran valid di $mw ($key)", !$bad);
        } else {
            check("middleware '$mw' dikenal ($key)", in_array($mw, ['auth', 'admin', 'guest'], true));
        }
    }
    $routes[] = [strtoupper($verb), $path];
}
// Rute statis tidak boleh tertutup rute berparameter yang didaftarkan lebih dulu.
foreach ($routes as $i => [$verb, $path]) {
    if (str_contains($path, '{')) {
        continue;
    }
    for ($j = 0; $j < $i; $j++) {
        [$v2, $p2] = $routes[$j];
        if ($v2 === $verb && str_contains($p2, '{')) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '[^/]+', $p2) . '$#';
            check("rute statis $path tidak tertutup $p2", !preg_match($regex, $path));
        }
    }
}
// Setiap POST harus lewat CSRF (dijamin Router); pastikan tidak ada rute yang mengecualikannya.
check('Router memeriksa CSRF untuk semua POST', str_contains(file_get_contents(BASE_DIR . '/app/core/Router.php'), "if (\$method === 'POST' && !Csrf::validate"));

// Server seperti Apache mengubah kode status tak baku (mis. 419) menjadi 500.
foreach (glob(BASE_DIR . '/app/{core,controllers,controllers/admin,models,helpers}/*.php', GLOB_BRACE) as $f) {
    preg_match_all('/\babort\(\s*(\d{3})/', file_get_contents($f), $am);
    foreach ($am[1] as $code) {
        check("abort($code) memakai kode HTTP baku (" . basename($f) . ')', in_array((int) $code, [400, 403, 404, 500], true));
    }
}

echo "== View yang dirujuk controller\n";
foreach (glob(BASE_DIR . '/app/controllers/{,admin/}*.php', GLOB_BRACE) as $f) {
    preg_match_all('/->view\(\s*\'([a-z0-9_\/]+)\'/', file_get_contents($f), $vm);
    foreach ($vm[1] as $view) {
        check("view $view ada (" . basename($f) . ')', is_file(BASE_DIR . "/app/views/$view.php"));
    }
}
foreach (['main', 'admin', 'print'] as $layout) {
    check("layout $layout ada", is_file(BASE_DIR . "/app/views/layouts/$layout.php"));
}

echo "== Berkas dan keamanan statis\n";
foreach (['app', 'config', 'database', 'storage', 'cron', 'vendor'] as $dir) {
    check("$dir/.htaccess menolak akses", is_file(BASE_DIR . "/$dir/.htaccess") && str_contains(file_get_contents(BASE_DIR . "/$dir/.htaccess"), 'denied'));
}
check('install.php terkunci bila sudah terpasang', str_contains(file_get_contents(PUBLIC_DIR . '/install.php'), 'installed.lock') && str_contains(file_get_contents(PUBLIC_DIR . '/install.php'), 'config.php'));
check('panduan deployment ada', is_file(BASE_DIR . '/docs/DEPLOYMENT.md'));
check('.htaccess mengecualikan .well-known (AutoSSL)', str_contains(file_get_contents(BASE_DIR . '/.htaccess'), '.well-known'));
check('config.php di-ignore git', str_contains(file_get_contents(BASE_DIR . '/.gitignore'), 'config/config.php'));
check('uploads publik tanpa eksekusi PHP', str_contains(file_get_contents(PUBLIC_DIR . '/uploads/.htaccess'), 'php'));
$schema = file_get_contents(BASE_DIR . '/database/schema.sql');
preg_match_all('/CREATE TABLE (\w+)/', $schema, $tm);
check('schema memuat 23 tabel', count($tm[1]) === 23, 'jumlah ' . count($tm[1]));
preg_match_all('/INSERT INTO `?(\w+)`?/', file_get_contents(BASE_DIR . '/database/seed.sql'), $sm);
check('seed hanya mengisi tabel yang ada', !array_diff(array_unique($sm[1]), $tm[1]));
foreach (['orders' => ['alamat_kirim', 'dp_jumlah']] as $t => $cols) {
    foreach ($cols as $c) {
        check("schema: $t.$c", (bool) preg_match('/CREATE TABLE ' . $t . ' \(.*?\b' . $c . '\b.*?\) ENGINE/s', $schema));
    }
}
// Semua kueri memakai prepared statement: tidak ada variabel input langsung di SQL controller.
foreach (glob(BASE_DIR . '/app/{controllers,controllers/admin,models}/*.php', GLOB_BRACE) as $f) {
    $src = file_get_contents($f);
    // Superglobal tidak boleh digabung ke string SQL (parameter terikat lewat array [...] boleh).
    check(basename($f) . ' tidak menggabungkan input ke string SQL', !preg_match('/[\'"]\s*\.\s*\$_(GET|POST|REQUEST)\b|"[^"\n]*\$_(GET|POST|REQUEST)/', $src));
}

echo "\n";
if ($fail) {
    echo count($fail) . " GAGAL, $pass lulus\n";
    exit(1);
}
echo "Semua lulus: $pass pemeriksaan\n";
