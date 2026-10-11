<?php
$isEdit = $material !== null;
$val = fn(string $k, $default = '') => old($k, $material[$k] ?? $default);
$action = $isEdit ? "/admin/bahan/{$material['id']}/ubah" : '/admin/bahan/tambah';
?>
<form method="post" action="<?= e(url($action)) ?>" class="form wide">
    <?= csrf_field() ?>
    <label>Nama bahan
        <input type="text" name="nama" value="<?= e($val('nama')) ?>" maxlength="100" placeholder="Contoh: Kain Cotton Combed 30s Hitam" required>
    </label>
    <label>Satuan
        <input type="text" name="satuan" value="<?= e($val('satuan', 'kg')) ?>" maxlength="20" list="satuan-list" required>
        <datalist id="satuan-list"><?php foreach (Material::UNITS as $u): ?><option value="<?= e($u) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Stok minimum (peringatan saat stok sama atau di bawah angka ini)
        <input type="number" name="stok_minimum" value="<?= e($val('stok_minimum', 0)) ?>" min="0" step="0.01" required>
    </label>
    <label>Supplier
        <select name="supplier_id">
            <option value="">- tanpa supplier -</option>
            <?php foreach ($suppliers as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= (int) $val('supplier_id', 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if (!$isEdit): ?>
    <label>Stok awal (opsional)
        <input type="number" name="stok_awal" value="<?= e(old('stok_awal')) ?>" min="0" step="0.01">
    </label>
    <?php else: ?>
        <p class="muted">Stok saat ini <?= e(Material::fmt((float) $material['stok'])) ?> <?= e($material['satuan']) ?>. Ubah stok lewat halaman bahan (stok masuk, pemakaian, atau koreksi) agar tercatat di riwayat.</p>
    <?php endif; ?>
    <div>
        <button class="btn" type="submit">Simpan</button>
        <a class="btn btn-outline" href="<?= e(url($isEdit ? "/admin/bahan/{$material['id']}" : '/admin/bahan')) ?>">Batal</a>
    </div>
</form>
