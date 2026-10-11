<h1>Katalog Produk</h1>

<form method="get" action="<?= e(url('/produk')) ?>" class="filters">
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari produk atau bahan...">
    <select name="kategori">
        <option value="">Semua kategori</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['slug']) ?>" <?= $filters['category'] === $c['slug'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Cari</button>
</form>

<?php if (!$result['items']): ?>
    <p class="muted">Produk tidak ditemukan.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($result['items'] as $p) { require BASE_DIR . '/app/views/products/_card.php'; } ?>
    </div>

    <?php if ($result['pages'] > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $result['pages']; $i++):
                $query = http_build_query(array_filter(['q' => $filters['q'], 'kategori' => $filters['category'], 'halaman' => $i])); ?>
                <a class="<?= $i === $result['page'] ? 'active' : '' ?>" href="<?= e(url('/produk?' . $query)) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
