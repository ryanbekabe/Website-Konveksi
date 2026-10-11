<h1>Pesan Custom</h1>
<p class="muted">Pilih produk, isi jumlah per ukuran (minimal <?= (int) $minQty ?> pcs), unggah desain, lalu kirim. Harga grosir dihitung otomatis dari total jumlah.</p>

<form method="post" action="<?= e(url('/pesan/custom')) ?>" enctype="multipart/form-data" class="form wide">
    <?= csrf_field() ?>

    <label>Produk
        <select name="product_id" required>
            <option value="">- pilih produk -</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $selected === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['nama']) ?> (mulai <?= e(rupiah($p['harga_dasar'])) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Warna
        <input type="text" name="warna" value="<?= e(old('warna')) ?>" maxlength="30" placeholder="Contoh: Hitam" required>
    </label>

    <fieldset>
        <legend>Jumlah per ukuran</legend>
        <div class="sizes">
            <?php $oldQty = old('qty', []); foreach ($sizes as $s): ?>
                <label><?= e($s) ?>
                    <input type="number" name="qty[<?= e($s) ?>]" min="0" max="10000" value="<?= (int) ($oldQty[$s] ?? 0) ?>">
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <label>File desain (JPG/PNG/WebP/PDF, maks. 5 MB)
        <input type="file" name="desain" accept="image/jpeg,image/png,image/webp,application/pdf">
    </label>

    <label>Catatan
        <textarea name="catatan" rows="3" maxlength="1000" placeholder="Posisi sablon, ukuran logo, deadline, dll."><?= e(old('catatan')) ?></textarea>
    </label>

    <?php require BASE_DIR . '/app/views/orders/_address_fields.php'; ?>

    <div><button class="btn" type="submit">Kirim Pesanan</button></div>
</form>
