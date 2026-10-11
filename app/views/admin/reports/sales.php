<?php
$path = '/admin/laporan/penjualan';
$exports = ['pesanan' => ['jenis' => 'pesanan'], 'produk' => ['jenis' => 'produk']];
$s = $report['sum'];
$max = max(1, ...array_map(fn($p) => (float) $p['omzet'], $report['periode'] ?: [['omzet' => 1]]));
?>
<p><a href="<?= e(url('/admin/laporan')) ?>" class="no-print">&larr; Semua laporan</a></p>
<?php require __DIR__ . '/_filter.php'; ?>

<div class="stats">
    <div class="stat"><span><?= e(rupiah($s['omzet'])) ?></span>Omzet (<?= (int) $s['pesanan'] ?> pesanan)</div>
    <div class="stat"><span><?= e(rupiah($s['kas_masuk'])) ?></span>Kas masuk (pembayaran terverifikasi)</div>
    <div class="stat <?= $s['piutang'] > 0 ? 'stat-warn' : '' ?>"><span><?= e(rupiah($s['piutang'])) ?></span>Piutang (belum lunas)</div>
    <div class="stat"><span><?= (int) $s['pcs'] ?></span>Jumlah barang (pcs)</div>
    <div class="stat"><span><?= e(rupiah($s['rata_rata'])) ?></span>Rata-rata per pesanan</div>
    <div class="stat"><span><?= (int) $s['dibatalkan'] ?></span>Pesanan dibatalkan (tidak dihitung)</div>
</div>
<p class="muted">Omzet = total pesanan yang dibuat pada periode (tidak termasuk yang dibatalkan), termasuk ongkir <?= e(rupiah($s['ongkir'])) ?>. Kas masuk dihitung dari tanggal pembayaran diverifikasi.</p>

<h2>Tren <?= $report['monthly'] ? 'per Bulan' : 'per Hari' ?></h2>
<?php if (!$report['periode']): ?>
    <p class="muted">Tidak ada penjualan pada periode ini.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th><?= $report['monthly'] ? 'Bulan' : 'Tanggal' ?></th><th>Pesanan</th><th>Omzet</th><th class="bar-col"></th></tr></thead>
    <tbody>
    <?php foreach ($report['periode'] as $p): ?>
        <tr>
            <td><?= e($p['periode']) ?></td>
            <td><?= (int) $p['pesanan'] ?></td>
            <td><?= e(rupiah($p['omzet'])) ?></td>
            <td class="bar-col"><div class="bar"><span style="width:<?= round((float) $p['omzet'] / $max * 100, 1) ?>%"></span></div></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<div class="cols-2">
    <div>
        <h2>Produk Terlaris</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Produk</th><th>Pcs</th><th>Omzet</th></tr></thead>
            <tbody>
            <?php foreach ($report['produk'] as $p): ?>
                <tr><td><?= e($p['nama']) ?></td><td><?= (int) $p['qty'] ?></td><td><?= e(rupiah($p['omzet'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$report['produk']): ?><tr><td colspan="3" class="muted">-</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
    <div>
        <h2>Pelanggan Teratas</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Pelanggan</th><th>Pesanan</th><th>Omzet</th></tr></thead>
            <tbody>
            <?php foreach ($report['pelanggan'] as $p): ?>
                <tr><td><?= e($p['nama']) ?></td><td><?= (int) $p['pesanan'] ?></td><td><?= e(rupiah($p['omzet'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$report['pelanggan']): ?><tr><td colspan="3" class="muted">-</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>

<h2>Pesanan per Status (termasuk dibatalkan)</h2>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Status</th><th>Pesanan</th><th>Nilai</th></tr></thead>
    <tbody>
    <?php foreach ($report['status'] as $st): ?>
        <tr><td><?= status_badge($st['status']) ?></td><td><?= (int) $st['pesanan'] ?></td><td><?= e(rupiah($st['omzet'])) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$report['status']): ?><tr><td colspan="3" class="muted">-</td></tr><?php endif; ?>
    </tbody>
</table></div>

<h2>Daftar Pesanan</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Status</th><th>Total</th><th>Terbayar</th><th>Sisa</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="<?= e(url('/admin/pesanan/' . $o['kode_order'])) ?>"><?= e($o['kode_order']) ?></a></td>
            <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
            <td><?= e($o['pelanggan']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td><?= e(rupiah($o['total'])) ?></td>
            <td><?= e(rupiah($o['terbayar'])) ?></td>
            <td><?= e(rupiah($o['sisa'])) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?><tr><td colspan="7" class="muted">Tidak ada pesanan.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<?php if (count($orders) >= 500): ?><p class="muted">Menampilkan 500 pesanan terbaru. Unduh CSV untuk daftar lengkap.</p><?php endif; ?>
