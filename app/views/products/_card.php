<?php /** @var array $p */ ?>
<a class="card" href="<?= e(url('/produk/' . $p['slug'])) ?>">
    <img src="<?= e(upload_url('products', $p['gambar'] ?? null)) ?>" alt="<?= e($p['nama']) ?>" loading="lazy">
    <div class="card-body">
        <small class="muted"><?= e($p['kategori'] ?? '') ?></small>
        <h3><?= e($p['nama']) ?></h3>
        <strong>Mulai <?= e(rupiah($p['harga_dasar'])) ?></strong>
    </div>
</a>
