<?php $lunas = $sisa <= 0 && $order['status'] !== 'dibatalkan'; ?>
<div class="doc">
    <div class="head">
        <div>
            <h1>INVOICE</h1>
            <div class="biz">
                <strong><?= e($usaha['nama']) ?></strong><br>
                <?= nl2br(e($usaha['alamat'])) ?><br>
                <?php if ($usaha['telepon']): ?>Telp/WA: <?= e($usaha['telepon']) ?><?php endif; ?>
            </div>
        </div>
        <div class="meta">
            <strong><?= e($order['kode_order']) ?></strong><br>
            Tanggal: <?= e(date('d M Y', strtotime($order['created_at']))) ?><br>
            Status pesanan: <?= e(Order::label($order['status'])) ?><br><br>
            <?php if ($order['status'] === 'dibatalkan'): ?>
                <span class="stamp belum">DIBATALKAN</span>
            <?php else: ?>
                <span class="stamp <?= $lunas ? 'lunas' : 'belum' ?>"><?= $lunas ? 'LUNAS' : 'BELUM LUNAS' ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="cols">
        <div>
            <h3>Ditagihkan kepada</h3>
            <strong><?= e($order['pelanggan']) ?></strong><br>
            <?= e($order['email']) ?><br><?= e($order['pelanggan_hp']) ?>
        </div>
        <div>
            <h3>Dikirim ke</h3>
            <?= nl2br(e($order['alamat_kirim'])) ?>
        </div>
    </div>

    <table>
        <thead><tr><th class="c" style="width:32px">No</th><th>Produk</th><th>Ukuran / Warna</th><th class="num">Harga</th><th class="c">Qty</th><th class="num">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($items as $n => $i): ?>
            <tr>
                <td class="c"><?= $n + 1 ?></td>
                <td><?= e($i['nama']) ?></td>
                <td><?= e(trim(($i['spec']['ukuran'] ?? '') . ' ' . ($i['spec']['warna'] ?? ''))) ?></td>
                <td class="num"><?= e(rupiah($i['harga_satuan'])) ?></td>
                <td class="c"><?= (int) $i['qty'] ?></td>
                <td class="num"><?= e(rupiah($i['harga_satuan'] * $i['qty'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num"><?= e(rupiah($order['subtotal'])) ?></td></tr>
        <tr><td>Ongkos kirim</td><td class="num"><?= e(rupiah($order['ongkir'])) ?></td></tr>
        <?php if ((float) $order['diskon'] > 0): ?>
            <tr><td>Diskon</td><td class="num">- <?= e(rupiah($order['diskon'])) ?></td></tr>
        <?php endif; ?>
        <tr class="grand"><td>Total</td><td class="num"><?= e(rupiah($order['total'])) ?></td></tr>
        <?php foreach ($payments as $p): ?>
            <tr><td><?= $p['jenis'] === 'dp' ? 'DP' : 'Pelunasan' ?> (<?= e(date('d M Y', strtotime($p['dibayar_pada'] ?: $p['created_at']))) ?>)</td><td class="num">- <?= e(rupiah($p['jumlah'])) ?></td></tr>
        <?php endforeach; ?>
        <tr class="grand"><td>Sisa tagihan</td><td class="num"><?= e(rupiah($sisa)) ?></td></tr>
    </table>

    <?php if (!$lunas && $order['status'] !== 'dibatalkan' && $usaha['rekening']): ?>
        <p><strong>Pembayaran melalui transfer ke:</strong></p>
        <div class="note"><?= e($usaha['rekening']) ?></div>
    <?php endif; ?>

    <?php if ($order['catatan']): ?>
        <p><strong>Catatan pesanan</strong></p>
        <div class="note"><?= e($order['catatan']) ?></div>
    <?php endif; ?>

    <p class="muted">Invoice ini dibuat otomatis oleh sistem dan sah tanpa tanda tangan. Pembayaran dianggap diterima setelah terverifikasi.</p>
</div>
