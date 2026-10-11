<?php
$val = fn(string $k) => old($k, $supplier[$k] ?? '');
$action = $supplier ? "/admin/supplier/{$supplier['id']}/ubah" : '/admin/supplier/tambah';
?>
<form method="post" action="<?= e(url($action)) ?>" class="form wide">
    <?= csrf_field() ?>
    <label>Nama supplier
        <input type="text" name="nama" value="<?= e($val('nama')) ?>" maxlength="100" required>
    </label>
    <label>Kontak (telepon / WhatsApp / email)
        <input type="text" name="kontak" value="<?= e($val('kontak')) ?>" maxlength="100">
    </label>
    <label>Alamat
        <textarea name="alamat" rows="3" maxlength="500"><?= e($val('alamat')) ?></textarea>
    </label>
    <div>
        <button class="btn" type="submit">Simpan</button>
        <a class="btn btn-outline" href="<?= e(url('/admin/supplier')) ?>">Batal</a>
    </div>
</form>
