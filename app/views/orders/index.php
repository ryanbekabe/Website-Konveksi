<h1>Pesanan Saya</h1>
<?php if (!$orders): ?>
    <p class="muted">Anda belum memiliki pesanan.</p>
    <a class="btn" href="<?= e(url('/pesan/custom')) ?>">Buat Pesanan Custom</a>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Kode</th><th>Tanggal</th><th>Status</th><th>Total</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><?= e($o['kode_order']) ?></td>
            <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td><?= e(rupiah($o['total'])) ?></td>
            <td><a href="<?= e(url('/pesanan/' . $o['kode_order'])) ?>">Detail</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
