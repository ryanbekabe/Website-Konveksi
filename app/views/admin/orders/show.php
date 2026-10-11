<?php
$kode = $order['kode_order'];
$canFinance = Auth::hasRole(['admin', 'keuangan']);
$canProduction = Auth::hasRole(['admin', 'produksi']);
?>
<p><a href="<?= e(url('/admin/pesanan')) ?>">&larr; Semua pesanan</a></p>
<p><?= status_badge($order['status']) ?>
   Pelanggan: <strong><?= e($order['pelanggan']) ?></strong> (<?= e($order['email']) ?>, <?= e($order['pelanggan_hp']) ?>)
   &middot; <?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></p>

<?php $status = $order['status']; require BASE_DIR . '/app/views/orders/_timeline.php'; ?>

<h2>Item</h2>
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
        <tr><td colspan="4">DP yang ditetapkan</td><td><?= e(rupiah($order['dp_jumlah'])) ?></td></tr>
        <tr><td colspan="4">Terbayar (terverifikasi)</td><td><?= e(rupiah($paid)) ?></td></tr>
    </tfoot>
</table>
</div>

<p><strong>Alamat kirim</strong><br><?= nl2br(e($order['alamat_kirim'])) ?></p>
<?php if ($order['catatan']): ?><p><strong>Catatan pelanggan</strong><br><?= nl2br(e($order['catatan'])) ?></p><?php endif; ?>
<?php if ($designs): ?>
    <p><strong>Desain pelanggan</strong>:
    <?php foreach ($designs as $d): ?>
        <a href="<?= e(url('/berkas/desain/' . (int) $d['id'])) ?>" target="_blank" rel="noopener">buka</a>
    <?php endforeach; ?></p>
<?php endif; ?>
<?php if ($shipment): ?><p><strong>Pengiriman</strong>: <?= e($shipment['kurir']) ?> - <code><?= e($shipment['resi']) ?></code></p><?php endif; ?>

<h2>Pembayaran</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Jenis</th><th>Jumlah</th><th>Status</th><th>Bukti</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
        <tr>
            <td><?= e($p['jenis']) ?></td>
            <td><?= e(rupiah($p['jumlah'])) ?></td>
            <td><?= e($p['status']) ?></td>
            <td><?php if ($p['bukti']): ?><a href="<?= e(url('/berkas/bukti/' . (int) $p['id'])) ?>" target="_blank" rel="noopener">lihat</a><?php endif; ?></td>
            <td>
                <?php if ($p['status'] === 'menunggu' && $canFinance): ?>
                <form method="post" action="<?= e(url('/admin/pembayaran/' . (int) $p['id'] . '/verifikasi')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm" name="aksi" value="terima" type="submit">Terima</button>
                    <button class="btn btn-sm btn-outline" name="aksi" value="tolak" type="submit">Tolak</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="5" class="muted">Belum ada pembayaran.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($canFinance && !in_array($order['status'], ['dikirim', 'selesai', 'dibatalkan'], true)): ?>
<form method="post" action="<?= e(url("/admin/pesanan/$kode/ongkir")) ?>" class="inline-form">
    <?= csrf_field() ?>
    <label>Ongkir (Rp) <input type="number" name="ongkir" min="0" step="500" value="<?= e((float) $order['ongkir']) ?>"></label>
    <button class="btn btn-sm" type="submit">Simpan ongkir</button>
</form>
<?php endif; ?>

<?php if ($canProduction): ?>
<h2>Mockup</h2>
<?php foreach ($mockups as $m): ?>
    <p><a href="<?= e(url('/berkas/desain/' . (int) $m['id'])) ?>" target="_blank" rel="noopener">Mockup #<?= (int) $m['id'] ?></a>
       &middot; <?= e($m['status_approval']) ?><?= $m['catatan'] ? ' - ' . e($m['catatan']) : '' ?></p>
<?php endforeach; ?>
<?php if (in_array($order['status'], ['dp_dibayar', 'desain_mockup', 'menunggu_persetujuan'], true)): ?>
<form method="post" action="<?= e(url("/admin/pesanan/$kode/mockup")) ?>" enctype="multipart/form-data" class="inline-form">
    <?= csrf_field() ?>
    <input type="file" name="mockup" accept="image/jpeg,image/png,image/webp,application/pdf" required>
    <button class="btn btn-sm" type="submit">Unggah &amp; kirim ke pelanggan</button>
</form>
<?php endif; ?>

<?php if ($next): ?>
<h2>Ubah Status</h2>
<form method="post" action="<?= e(url("/admin/pesanan/$kode/status")) ?>" class="inline-form">
    <?= csrf_field() ?>
    <select name="status">
        <?php foreach ($next as $s): ?><option value="<?= e($s) ?>"><?= e(Order::label($s)) ?></option><?php endforeach; ?>
    </select>
    <input type="text" name="kurir" placeholder="Kurir (jika dikirim)" maxlength="50">
    <input type="text" name="resi" placeholder="No. resi (jika dikirim)" maxlength="80">
    <button class="btn btn-sm" type="submit">Simpan</button>
</form>
<?php endif; ?>
<?php endif; ?>

<?php if ($stages): ?>
<h2>Tahapan Produksi</h2>
<ul>
    <?php foreach ($stages as $st): ?><li><?= e(str_replace('_', ' / ', $st['tahap'])) ?>: <?= e($st['status']) ?></li><?php endforeach; ?>
</ul>
<p class="muted">Pengelolaan tahapan produksi (penugasan, mulai/selesai) akan ditambahkan pada tahap berikutnya.</p>
<?php endif; ?>
