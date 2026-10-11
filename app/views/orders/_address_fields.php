<?php $u = Auth::user(); ?>
<fieldset class="form">
    <legend>Alamat pengiriman</legend>
    <label>Nama penerima
        <input type="text" name="penerima" value="<?= e(old('penerima', $u['nama'] ?? '')) ?>" maxlength="100" required>
    </label>
    <label>No. HP penerima
        <input type="tel" name="no_hp" value="<?= e(old('no_hp', $u['no_hp'] ?? '')) ?>" required>
    </label>
    <label>Alamat lengkap
        <textarea name="alamat" rows="3" maxlength="500" required><?= e(old('alamat')) ?></textarea>
    </label>
    <label>Kota/Kabupaten
        <input type="text" name="kota" value="<?= e(old('kota')) ?>" maxlength="100" required>
    </label>
    <label>Provinsi
        <input type="text" name="provinsi" value="<?= e(old('provinsi')) ?>" maxlength="100" required>
    </label>
    <label>Kode pos
        <input type="text" name="kode_pos" value="<?= e(old('kode_pos')) ?>" maxlength="10">
    </label>
</fieldset>
