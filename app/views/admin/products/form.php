<?php
$isEdit = $product !== null;
$val = fn(string $k, $default = '') => old($k, $product[$k] ?? $default);
$action = $isEdit ? "/admin/produk/{$product['id']}/ubah" : '/admin/produk/tambah';
?>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="form wide">
    <?= csrf_field() ?>

    <label>Nama produk
        <input type="text" name="nama" value="<?= e($val('nama')) ?>" maxlength="150" required>
    </label>

    <label>Kategori
        <select name="category_id" required>
            <option value="">- pilih -</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $val('category_id', 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Tipe
        <select name="tipe">
            <?php foreach (['custom' => 'Custom (sablon/bordir)', 'ready' => 'Ready stock'] as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $val('tipe', 'custom') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Bahan
        <input type="text" name="bahan" value="<?= e($val('bahan')) ?>" maxlength="100" placeholder="Contoh: Cotton Combed 30s">
    </label>

    <label>Harga dasar (Rp / pcs)
        <input type="number" name="harga_dasar" value="<?= e($val('harga_dasar', 0)) ?>" min="0" step="500" required>
    </label>

    <label>Deskripsi
        <textarea name="deskripsi" rows="5"><?= e($val('deskripsi')) ?></textarea>
    </label>

    <label>Gambar (JPG/PNG/WebP, maks. <?= round(config('upload.max_size') / 1048576, 1) ?> MB)
        <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
    </label>
    <?php if ($isEdit && !empty($product['gambar'])): ?>
        <img class="thumb large" src="<?= e(upload_url('products', $product['gambar'])) ?>" alt="">
    <?php endif; ?>

    <label class="check">
        <input type="checkbox" name="aktif" value="1" <?= (int) $val('aktif', 1) ? 'checked' : '' ?>> Tampilkan di katalog
    </label>

    <div>
        <button class="btn" type="submit">Simpan</button>
        <a class="btn btn-outline" href="<?= e(url('/admin/produk')) ?>">Batal</a>
    </div>
</form>
