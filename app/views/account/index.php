<section class="narrow">
    <h1>Akun Saya</h1>
    <p class="muted"><?= e($user['email']) ?> &middot; <?= e(User::ROLES[$user['role']] ?? $user['role']) ?></p>

    <h2>Profil</h2>
    <form method="post" action="<?= e(url('/akun')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Nama lengkap
            <input type="text" name="nama" value="<?= e(old('nama', $user['nama'])) ?>" maxlength="100" required>
        </label>
        <label>No. HP / WhatsApp
            <input type="tel" name="no_hp" value="<?= e(old('no_hp', $user['no_hp'])) ?>">
        </label>
        <div><button class="btn" type="submit">Simpan Profil</button></div>
    </form>

    <h2>Ganti Kata Sandi</h2>
    <form method="post" action="<?= e(url('/akun/sandi')) ?>" class="form" autocomplete="off">
        <?= csrf_field() ?>
        <label>Kata sandi saat ini
            <input type="password" name="password_lama" required autocomplete="current-password">
        </label>
        <label>Kata sandi baru (min. 8 karakter)
            <input type="password" name="password_baru" minlength="8" maxlength="72" required autocomplete="new-password">
        </label>
        <label>Ulangi kata sandi baru
            <input type="password" name="password_konfirmasi" minlength="8" maxlength="72" required autocomplete="new-password">
        </label>
        <div><button class="btn" type="submit">Ganti Kata Sandi</button></div>
    </form>
</section>
