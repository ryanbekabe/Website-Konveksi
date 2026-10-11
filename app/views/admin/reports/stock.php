<?php
$path = '/admin/laporan/stok';
$exports = ['posisi stok' => ['jenis' => 'bahan'], 'mutasi' => ['jenis' => 'mutasi']];
$s = $report['sum'];
?>
<p><a href="<?= e(url('/admin/laporan')) ?>" class="no-print">&larr; Semua laporan</a></p>
<?php require __DIR__ . '/_filter.php'; ?>

<div class="stats">
    <div class="stat"><span><?= (int) $s['bahan'] ?></span>Jenis bahan</div>
    <div class="stat <?= $s['menipis'] ? 'stat-warn' : '' ?>"><span><?= (int) $s['menipis'] ?></span>Bahan menipis (saat ini)</div>
    <div class="stat"><span><?= (int) $s['mutasi'] ?></span>Mutasi stok pada periode</div>
</div>
<p class="muted">Stok saat ini adalah posisi terbaru, tidak bergantung pada periode. Masuk dan Keluar dihitung dari mutasi pada periode (termasuk penyesuaian stok).</p>

<h2>Posisi Stok</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Bahan</th><th>Supplier</th><th>Stok saat ini</th><th>Minimum</th><th>Masuk (periode)</th><th>Keluar (periode)</th></tr></thead>
    <tbody>
    <?php foreach ($report['materials'] as $m): ?>
        <tr>
            <td><a href="<?= e(url('/admin/bahan/' . (int) $m['id'])) ?>"><?= e($m['nama']) ?></a></td>
            <td><?= e($m['supplier'] ?? '-') ?></td>
            <td><strong><?= e(Material::fmt((float) $m['stok'])) ?></strong> <?= e($m['satuan']) ?> <?= $m['menipis'] ? '<span class="badge badge-danger">Menipis</span>' : '' ?></td>
            <td><?= e(Material::fmt((float) $m['stok_minimum'])) ?></td>
            <td>+<?= e(Material::fmt((float) $m['masuk'])) ?></td>
            <td>-<?= e(Material::fmt((float) $m['keluar'])) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$report['materials']): ?><tr><td colspan="6" class="muted">Belum ada bahan.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<h2>Pemakaian Bahan per Pesanan</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Pesanan</th><th>Rincian pemakaian</th></tr></thead>
    <tbody>
    <?php foreach ($report['pemakaian'] as $p): ?>
        <tr>
            <td><a href="<?= e(url('/admin/pesanan/' . $p['kode_order'])) ?>"><?= e($p['kode_order']) ?></a></td>
            <td><?= e($p['rincian']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$report['pemakaian']): ?><tr><td colspan="2" class="muted">Tidak ada pemakaian yang dikaitkan dengan pesanan pada periode ini.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
