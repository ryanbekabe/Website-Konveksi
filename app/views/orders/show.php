<?php $sisa = (float) $order['total'] - $paid; ?>
<div class="toolbar">
    <h1><?= e($order['kode_order']) ?> <?= status_badge($order['status']) ?></h1>
    <span>
        <a href="<?= e(url('/pesanan/' . $order['kode_order'] . '/invoice')) ?>" target="_blank" rel="noopener">Lihat / cetak invoice</a> &middot;
        <a href="<?= e(url('/pesanan')) ?>">&larr; Semua pesanan</a>
    </span>
</div>

<?php $status = $order['status']; require BASE_DIR . '/app/views/orders/_timeline.php'; ?>

<?php if ($stages): $sum = ProductionStage::summarize($stages); ?>
<h2>Progres Produksi</h2>
<div class="progress" title="<?= (int) $sum['percent'] ?>%"><span style="width:<?= (int) $sum['percent'] ?>%"></span></div>
<ul class="stage-list">
    <?php foreach ($stages as $st): ?>
        <li class="<?= e($st['status']) ?>">
            <?= e(Order::stageLabel($st['tahap'])) ?> - <?= e(ProductionStage::STATUS[$st['status']]) ?>
            <?= $st['selesai'] ? '<small class="muted">(' . e(date('d M H:i', strtotime($st['selesai']))) . ')</small>' : '' ?>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<h2>Rincian</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Produk</th><th>Ukuran / Warna</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
        <tr>
            <td><?= e($i['nama']) ?></td>
            <td><?= e(trim(($i['spec']['ukuran'] ?? '') . ' ' . ($i['spec']['warna'] ?? ''))) ?></td>
            <td><?= e(rupiah($i['harga_satuan'])) ?></td>
            <td><?= (int) $i['qty'] ?></td>
            <td><?= e(rupiah($i['harga_satuan'] * $i['qty'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="4">Subtotal</td><td><?= e(rupiah($order['subtotal'])) ?></td></tr>
        <tr><td colspan="4">Ongkir</td><td><?= e(rupiah($order['ongkir'])) ?></td></tr>
        <tr><th colspan="4">Total</th><th><?= e(rupiah($order['total'])) ?></th></tr>
        <tr><td colspan="4">Sudah dibayar (terverifikasi)</td><td><?= e(rupiah($paid)) ?></td></tr>
        <tr><td colspan="4">Sisa tagihan</td><td><?= e(rupiah(max(0, $sisa))) ?></td></tr>
    </tfoot>
</table>
</div>

<p><strong>Alamat kirim</strong><br><?= nl2br(e($order['alamat_kirim'])) ?></p>
<?php if ($order['catatan']): ?><p><strong>Catatan</strong><br><?= nl2br(e($order['catatan'])) ?></p><?php endif; ?>
<?php if ($order['estimasi_selesai']): ?>
    <p><strong>Estimasi selesai</strong><br><?= e(date('d M Y', strtotime($order['estimasi_selesai']))) ?></p>
<?php endif; ?>
<?php if ($shipment && $shipment['resi']): ?>
    <p><strong>Pengiriman</strong><br><?= e($shipment['kurir']) ?> - resi <code><?= e($shipment['resi']) ?></code></p>
<?php endif; ?>

<?php if ($designs): ?>
    <p><strong>File desain Anda</strong>:
    <?php foreach ($designs as $d): ?>
        <a href="<?= e(url('/berkas/desain/' . (int) $d['id'])) ?>" target="_blank" rel="noopener">lihat</a>
    <?php endforeach; ?></p>
<?php endif; ?>

<?php if ($next): ?>
<section class="panel">
    <h2>Pembayaran <?= $next['jenis'] === 'dp' ? 'DP' : 'Pelunasan' ?></h2>
    <p>Transfer sebesar <strong><?= e(rupiah($next['jumlah'])) ?></strong> ke:</p>
    <p><code><?= e($rekening) ?></code></p>
    <?php $pending = array_filter($payments, fn($p) => $p['jenis'] === $next['jenis'] && $p['status'] === 'menunggu'); ?>
    <?php if ($pending): ?>
        <p class="muted">Bukti pembayaran Anda sedang diverifikasi admin.</p>
    <?php else: ?>
        <form method="post" action="<?= e(url('/pesanan/' . $order['kode_order'] . '/bayar')) ?>" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <label>Bukti transfer (JPG/PNG/WebP/PDF, maks. 5 MB)
                <input type="file" name="bukti" accept="image/jpeg,image/png,image/webp,application/pdf" required>
            </label>
            <div><button class="btn" type="submit">Kirim Bukti</button></div>
        </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($order['status'] === 'menunggu_persetujuan' && $mockup): ?>
<section class="panel">
    <h2>Persetujuan Mockup</h2>
    <p><a class="btn btn-sm" href="<?= e(url('/berkas/desain/' . (int) $mockup['id'])) ?>" target="_blank" rel="noopener">Lihat mockup</a></p>
    <form method="post" action="<?= e(url('/pesanan/' . $order['kode_order'] . '/mockup')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Catatan revisi (wajib bila meminta revisi)
            <textarea name="catatan" rows="3" maxlength="1000"></textarea>
        </label>
        <div>
            <button class="btn" type="submit" name="aksi" value="setuju">Setujui &amp; Mulai Produksi</button>
            <button class="btn btn-outline" type="submit" name="aksi" value="revisi">Minta Revisi</button>
        </div>
    </form>
</section>
<?php elseif ($mockups && $order['status'] === 'desain_mockup'): ?>
    <?php $last = end($mockups); if ($last['status_approval'] === 'revisi'): ?>
        <p class="alert alert-error">Revisi diminta: <?= e($last['catatan']) ?>. Kami sedang memperbarui mockup.</p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($payments): ?>
<h2>Riwayat Pembayaran</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Jenis</th><th>Jumlah</th><th>Status</th><th>Bukti</th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
        <tr>
            <td><?= e($p['jenis']) ?></td>
            <td><?= e(rupiah($p['jumlah'])) ?></td>
            <td><?= e($p['status']) ?></td>
            <td><?php if ($p['bukti']): ?><a href="<?= e(url('/berkas/bukti/' . (int) $p['id'])) ?>" target="_blank" rel="noopener">lihat</a><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php if ($order['status'] === 'menunggu_pembayaran'): ?>
    <form method="post" action="<?= e(url('/pesanan/' . $order['kode_order'] . '/batal')) ?>" onsubmit="return confirm('Batalkan pesanan ini?')">
        <?= csrf_field() ?>
        <button class="link danger" type="submit">Batalkan pesanan</button>
    </form>
<?php endif; ?>
