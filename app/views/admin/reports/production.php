<?php
$path = '/admin/laporan/produksi';
$exports = ['pesanan selesai' => ['jenis' => 'pesanan'], 'rincian tahap' => ['jenis' => 'tahap']];
$s = $report['sum'];
$fmtJam = fn($j) => $j === null ? '-' : ($j >= 48 ? round($j / 24, 1) . ' hari' : round((float) $j, 1) . ' jam');
?>
<p><a href="<?= e(url('/admin/laporan')) ?>" class="no-print">&larr; Semua laporan</a></p>
<?php require __DIR__ . '/_filter.php'; ?>

<div class="stats">
    <div class="stat"><span><?= (int) $s['selesai'] ?></span>Pesanan selesai produksi (<?= (int) $s['pcs'] ?> pcs)</div>
    <div class="stat"><span><?= (int) $s['mulai'] ?></span>Pesanan mulai produksi</div>
    <div class="stat"><span><?= $s['persen_tepat'] === null ? '-' : (int) $s['persen_tepat'] . '%' ?></span>Tepat waktu (<?= (int) $s['tepat_waktu'] ?> dari <?= (int) $s['dengan_estimasi'] ?> yang punya estimasi)</div>
    <div class="stat"><span><?= $s['rata_hari'] === null ? '-' : e($s['rata_hari']) . ' hari' ?></span>Rata-rata lama produksi</div>
    <div class="stat"><span><?= (int) $s['wip'] ?></span>Sedang diproduksi (saat ini)</div>
    <div class="stat <?= $s['wip_terlambat'] ? 'stat-warn' : '' ?>"><span><?= (int) $s['wip_terlambat'] ?></span>Terlambat (saat ini)</div>
</div>
<p class="muted">Pesanan dihitung selesai produksi saat tahap Packing selesai pada periode. Tepat waktu berarti selesai pada atau sebelum tanggal estimasi. Ringkasan "saat ini" tidak bergantung pada periode.</p>

<div class="cols-2">
    <div>
        <h2>Per Tahap</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Tahap</th><th>Selesai</th><th>Rata-rata durasi</th></tr></thead>
            <tbody>
            <?php foreach ($report['tahap'] as $t): ?>
                <tr><td><?= e(Order::stageLabel($t['tahap'])) ?></td><td><?= (int) $t['selesai'] ?></td><td><?= e($fmtJam($t['rata_jam'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$report['tahap']): ?><tr><td colspan="3" class="muted">Belum ada tahap selesai.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
    <div>
        <h2>Per Petugas</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Petugas</th><th>Tahap selesai</th><th>Rata-rata durasi</th></tr></thead>
            <tbody>
            <?php foreach ($report['petugas'] as $t): ?>
                <tr><td><?= e($t['nama']) ?></td><td><?= (int) $t['selesai'] ?></td><td><?= e($fmtJam($t['rata_jam'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$report['petugas']): ?><tr><td colspan="3" class="muted">-</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>

<h2>Pesanan Selesai Produksi</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Kode</th><th>Pelanggan</th><th>Pcs</th><th>Mulai</th><th>Selesai</th><th>Lama</th><th>Estimasi</th><th>Tepat waktu</th></tr></thead>
    <tbody>
    <?php foreach ($report['done'] as $d): ?>
        <tr>
            <td><a href="<?= e(url('/admin/pesanan/' . $d['kode_order'])) ?>"><?= e($d['kode_order']) ?></a></td>
            <td><?= e($d['pelanggan']) ?></td>
            <td><?= (int) $d['pcs'] ?></td>
            <td><?= $d['mulai'] ? e(date('d M Y', strtotime($d['mulai']))) : '-' ?></td>
            <td><?= e(date('d M Y', strtotime($d['selesai']))) ?></td>
            <td><?= e($fmtJam($d['lama_jam'])) ?></td>
            <td><?= $d['estimasi_selesai'] ? e(date('d M Y', strtotime($d['estimasi_selesai']))) : '-' ?></td>
            <td><?= $d['tepat_waktu'] === null ? '-' : ($d['tepat_waktu'] ? '<span class="badge badge-ok">Ya</span>' : '<span class="badge badge-danger">Terlambat</span>') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$report['done']): ?><tr><td colspan="8" class="muted">Tidak ada pesanan yang selesai produksi pada periode ini.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
