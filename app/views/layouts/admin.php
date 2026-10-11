<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Admin') . ' - Admin') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="admin">
<div class="admin-wrap">
    <aside class="sidebar">
        <a class="brand" href="<?= e(url('/admin')) ?>"><?= e(config('app.name')) ?></a>
        <nav>
            <a class="<?= $title === 'Dashboard' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>">Dashboard</a>
            <a class="<?= trim(is_active('/admin/pesanan')) ?>" href="<?= e(url('/admin/pesanan')) ?>">Pesanan</a>
            <?php if (Auth::hasRole(['admin', 'produksi'])): ?>
            <a class="<?= trim(is_active('/admin/produksi')) ?>" href="<?= e(url('/admin/produksi')) ?>">Produksi</a>
            <?php endif; ?>
            <?php if (Auth::hasRole(['admin'])): ?>
            <a class="<?= trim(is_active('/admin/produk')) ?>" href="<?= e(url('/admin/produk')) ?>">Produk</a>
            <?php endif; ?>
            <a class="<?= trim(is_active('/admin/laporan')) ?>" href="<?= e(url('/admin/laporan')) ?>">Laporan</a>
            <a class="<?= trim(is_active('/admin/bahan')) ?>" href="<?= e(url('/admin/bahan')) ?>">Bahan Baku</a>
            <?php if (Auth::hasRole(['admin'])): ?>
            <a class="<?= trim(is_active('/admin/supplier')) ?>" href="<?= e(url('/admin/supplier')) ?>">Supplier</a>
            <a class="<?= trim(is_active('/admin/pengguna')) ?>" href="<?= e(url('/admin/pengguna')) ?>">Pengguna</a>
            <?php endif; ?>
            <a class="<?= trim(is_active('/notifikasi')) ?>" href="<?= e(url('/notifikasi')) ?>">Notifikasi<?= unread_notifications() ? ' <span class="badge badge-danger">' . unread_notifications() . '</span>' : '' ?></a>
            <a class="<?= trim(is_active('/akun')) ?>" href="<?= e(url('/akun')) ?>">Akun Saya</a>
            <a href="<?= e(url('/')) ?>">Lihat Situs</a>
        </nav>
        <form method="post" action="<?= e(url('/keluar')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-block">Keluar</button>
        </form>
    </aside>
    <section class="admin-main">
        <h1><?= e($title ?? '') ?></h1>
        <?php foreach (flash_messages() as $type => $messages): foreach ($messages as $msg): ?>
            <div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div>
        <?php endforeach; endforeach; ?>
        <?= $content ?>
    </section>
</div>
</body>
</html>
