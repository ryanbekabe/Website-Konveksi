<section class="hero">
    <h1>Konveksi &amp; Sablon Berkualitas</h1>
    <p>Kaos, kemeja, jaket, hoodie, dan seragam. Pesan satuan maupun partai besar dengan desain sendiri.</p>
    <p>
        <a class="btn" href="<?= e(url('/produk')) ?>">Lihat Katalog</a>
        <a class="btn btn-outline" href="<?= e(whatsapp_link('Halo, saya ingin konsultasi pesanan konveksi.')) ?>" target="_blank" rel="noopener">Konsultasi via WhatsApp</a>
    </p>
</section>

<?php if ($categories): ?>
<section>
    <h2>Kategori</h2>
    <div class="chips">
        <?php foreach ($categories as $c): ?>
            <a class="chip" href="<?= e(url('/produk?kategori=' . rawurlencode($c['slug']))) ?>"><?= e($c['nama']) ?></a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section>
    <h2>Produk Terbaru</h2>
    <?php if (!$products): ?>
        <p class="muted">Belum ada produk.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($products as $p) { require BASE_DIR . '/app/views/products/_card.php'; } ?>
        </div>
    <?php endif; ?>
</section>
