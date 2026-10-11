<?php
$isEdit = $user !== null;
$val = fn(string $k, $default = '') => old($k, $user[$k] ?? $default);
$action = $isEdit ? "/admin/pengguna/{$user['id']}/ubah" : '/admin/pengguna/tambah';
?>
<form method="post" action="<?= e(url($action)) ?>" class="form wide" autocomplete="off">
    <?= csrf_field() ?>

    <label>Nama lengkap
        <input type="text" name="nama" value="<?= e($val('nama')) ?>" maxlength="100" required>
    </label>
    <label>Email (dipakai untuk login)
        <input type="email" name="email" value="<?= e($val('email')) ?>" maxlength="100" required>
    </label>
    <label>No. HP / WhatsApp
        <input type="tel" name="no_hp" value="<?= e($val('no_hp')) ?>">
    </label>

    <label>Peran
        <select name="role" <?= $self ? 'disabled' : '' ?>>
            <?php foreach (User::ROLES as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $val('role', 'produksi') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if ($self): ?><p class="muted">Anda tidak dapat mengubah peran atau menonaktifkan akun sendiri.</p><?php endif; ?>

    <?php if ($isEdit): ?>
    <label>Status
        <select name="status" <?= $self ? 'disabled' : '' ?>>
            <option value="aktif" <?= $val('status') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $val('status') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (tidak bisa login)</option>
        </select>
    </label>
    <?php endif; ?>

    <label><?= $isEdit ? 'Reset kata sandi (kosongkan bila tidak diubah)' : 'Kata sandi (min. 8 karakter)' ?>
        <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    </label>

    <div>
        <button class="btn" type="submit">Simpan</button>
        <a class="btn btn-outline" href="<?= e(url('/admin/pengguna')) ?>">Batal</a>
    </div>
</form>
