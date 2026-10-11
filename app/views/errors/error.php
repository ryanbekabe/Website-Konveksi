<section class="center narrow">
    <h1><?= (int) $code ?> - <?= e($title) ?></h1>
    <?php if ($message !== ''): ?><p><?= e($message) ?></p><?php endif; ?>
    <p><a class="btn" href="<?= e(url('/')) ?>">Kembali ke Beranda</a></p>
</section>
