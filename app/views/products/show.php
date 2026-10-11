<div class="detail">
    <img src="<?= e(upload_url('products', $product['gambar'] ?? null)) ?>" alt="<?= e($product['nama']) ?>">

    <div>
        <small class="muted"><?= e($product['kategori'] ?? '') ?></small>
        <h1><?= e($product['nama']) ?></h1>
        <p class="price"><?= e(rupiah($product['harga_dasar'])) ?> <small class="muted">/ pcs</small></p>
        <?php if ($product['bahan']): ?><p>Bahan: <strong><?= e($product['bahan']) ?></strong></p><?php endif; ?>
        <p><?= nl2br(e($product['deskripsi'])) ?></p>

        <?php if ($tiers): ?>
            <h3>Harga Grosir</h3>
            <table class="table">
                <thead><tr><th>Jumlah</th><th>Harga / pcs</th></tr></thead>
                <tbody>
                <?php foreach ($tiers as $t): ?>
                    <tr>
                        <td><?= (int) $t['min_qty'] ?><?= $t['max_qty'] === null ? '+' : ' - ' . (int) $t['max_qty'] ?> pcs</td>
                        <td><?= e(rupiah($t['harga'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/keranjang/tambah')) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <label>Ukuran
                <select name="ukuran">
                    <?php foreach ($sizes as $s): ?><option><?= e($s) ?></option><?php endforeach; ?>
                </select>
            </label>
            <?php if ($colors): ?>
            <label>Warna
                <select name="warna">
                    <?php foreach ($colors as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
            <label>Jumlah
                <input type="number" name="qty" value="1" min="1" max="10000" required>
            </label>
            <button class="btn" type="submit">Tambah ke Keranjang</button>
        </form>
    </div>
</div>
