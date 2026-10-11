<h1>Checkout</h1>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Produk</th><th>Ukuran / Warna</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['product']['nama']) ?></td>
            <td><?= e(trim($r['ukuran'] . ' ' . $r['warna'])) ?></td>
            <td><?= e(rupiah($r['unit'])) ?></td>
            <td><?= (int) $r['qty'] ?></td>
            <td><?= e(rupiah($r['subtotal'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th colspan="4">Total (belum termasuk ongkir)</th><th><?= e(rupiah($total)) ?></th></tr>
        <tr><td colspan="4">DP (<?= e(rtrim(rtrim(number_format($dpPersen, 2, '.', ''), '0'), '.')) ?>%)</td><td><?= e(rupiah($dp)) ?></td></tr>
    </tfoot>
</table>
</div>

<form method="post" action="<?= e(url('/checkout')) ?>" enctype="multipart/form-data" class="form wide">
    <?= csrf_field() ?>
    <?php require BASE_DIR . '/app/views/orders/_address_fields.php'; ?>

    <label>File desain (opsional, JPG/PNG/WebP/PDF, maks. 5 MB)
        <input type="file" name="desain" accept="image/jpeg,image/png,image/webp,application/pdf">
    </label>
    <label>Catatan pesanan
        <textarea name="catatan" rows="3" maxlength="1000" placeholder="Posisi sablon, ukuran logo, deadline, dll."><?= e(old('catatan')) ?></textarea>
    </label>
    <p class="muted">Ongkir akan ditambahkan admin setelah pesanan dibuat. Pesanan baru diproses setelah DP terverifikasi.</p>
    <div>
        <button class="btn" type="submit">Buat Pesanan</button>
        <a class="btn btn-outline" href="<?= e(url('/keranjang')) ?>">Kembali</a>
    </div>
</form>
