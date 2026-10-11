<h1>Keranjang</h1>

<?php if (!$rows): ?>
    <p class="muted">Keranjang masih kosong.</p>
    <a class="btn" href="<?= e(url('/produk')) ?>">Lihat Katalog</a>
<?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Produk</th><th>Ukuran / Warna</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><a href="<?= e(url('/produk/' . $r['product']['slug'])) ?>"><?= e($r['product']['nama']) ?></a></td>
                <td><?= e(trim($r['ukuran'] . ' ' . $r['warna'])) ?></td>
                <td><?= e(rupiah($r['unit'])) ?></td>
                <td>
                    <form method="post" action="<?= e(url('/keranjang/ubah')) ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="key" value="<?= e($r['key']) ?>">
                        <input type="number" name="qty" value="<?= (int) $r['qty'] ?>" min="1" max="10000" class="qty">
                        <button class="btn btn-sm" type="submit">Ubah</button>
                    </form>
                </td>
                <td><?= e(rupiah($r['subtotal'])) ?></td>
                <td>
                    <form method="post" action="<?= e(url('/keranjang/hapus')) ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="key" value="<?= e($r['key']) ?>">
                        <button class="link danger" type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="4">Total</th><th colspan="2"><?= e(rupiah($total)) ?></th></tr></tfoot>
    </table>
    </div>
    <p><a class="btn" href="<?= e(url('/checkout')) ?>">Lanjut ke Checkout</a></p>
<?php endif; ?>
