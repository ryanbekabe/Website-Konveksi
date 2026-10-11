<div class="stats">
    <div class="stat"><span><?= (int) $stats['produk'] ?></span>Produk</div>
    <div class="stat"><span><?= (int) $stats['pelanggan'] ?></span>Pelanggan</div>
    <div class="stat"><span><?= (int) $stats['pesanan'] ?></span>Pesanan</div>
    <div class="stat"><span><?= e(rupiah($stats['omzet'])) ?></span>Omzet (selesai)</div>
    <div class="stat"><span><?= (int) $stats['stok_menipis'] ?></span>Bahan menipis</div>
</div>

<h2>Pesanan Terbaru</h2>
<?php if (!$orders): ?>
    <p class="muted">Belum ada pesanan.</p>
<?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Status</th><th>Total</th><th>Tanggal</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="<?= e(url('/admin/pesanan/' . $o['kode_order'])) ?>"><?= e($o['kode_order']) ?></a></td>
                <td><?= e($o['nama']) ?></td>
                <td><?= status_badge($o['status']) ?></td>
                <td><?= e(rupiah($o['total'])) ?></td>
                <td><?= e($o['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
