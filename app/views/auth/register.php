<section class="narrow">
    <h1>Daftar Akun</h1>
    <form method="post" action="<?= e(url('/daftar')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Nama lengkap
            <input type="text" name="nama" value="<?= e(old('nama')) ?>" maxlength="100" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e(old('email')) ?>" maxlength="100" required>
        </label>
        <label>No. HP / WhatsApp
            <input type="tel" name="no_hp" value="<?= e(old('no_hp')) ?>" required>
        </label>
        <label>Kata sandi (min. 8 karakter)
            <input type="password" name="password" minlength="8" maxlength="72" required>
        </label>
        <label>Ulangi kata sandi
            <input type="password" name="password_confirm" minlength="8" maxlength="72" required>
        </label>
        <button class="btn" type="submit">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="<?= e(url('/masuk')) ?>">Masuk</a></p>
</section>
