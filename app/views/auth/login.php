<section class="narrow">
    <h1>Masuk</h1>
    <form method="post" action="<?= e(url('/masuk')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
        </label>
        <label>Kata sandi
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">Masuk</button>
    </form>
    <p>Belum punya akun? <a href="<?= e(url('/daftar')) ?>">Daftar</a></p>
</section>
