<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') ?: config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= e(url('/')) ?>"><?= e(config('app.name')) ?></a>
        <nav>
            <a class="<?= trim(is_active('/')) === 'active' ? 'active' : '' ?>" href="<?= e(url('/')) ?>">Beranda</a>
            <a class="<?= trim(is_active('/produk')) ?>" href="<?= e(url('/produk')) ?>">Katalog</a>
            <a href="<?= e(url('/pesan/custom')) ?>">Pesan Custom</a>
            <a href="<?= e(url('/keranjang')) ?>">Keranjang (<?= (int) Cart::count() ?>)</a>
            <?php if (Auth::check()): ?>
                <a href="<?= e(url('/pesanan')) ?>">Pesanan Saya</a>
                <?php if (Auth::hasRole(['admin', 'produksi', 'keuangan'])): ?>
                    <a href="<?= e(url('/admin')) ?>">Admin</a>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/keluar')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <a href="<?= e(url('/akun')) ?>"><?= e(Auth::user()['nama'] ?? 'Akun') ?></a>
                    <button type="submit" class="link">Keluar</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('/masuk')) ?>">Masuk</a>
                <a class="btn btn-sm" href="<?= e(url('/daftar')) ?>">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <?php foreach (flash_messages() as $type => $messages): foreach ($messages as $msg): ?>
        <div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div>
    <?php endforeach; endforeach; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= e(config('app.name')) ?>. Semua hak dilindungi.</p>
    </div>
</footer>

<a class="wa-float" href="<?= e(whatsapp_link('Halo, saya ingin bertanya tentang pesanan konveksi.')) ?>" target="_blank" rel="noopener">WhatsApp</a>
</body>
</html>
