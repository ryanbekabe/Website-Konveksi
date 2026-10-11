<div class="toolbar">
    <form method="get" class="inline">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari produk...">
        <button class="btn btn-sm" type="submit">Cari</button>
    </form>
    <a class="btn" href="<?= e(url('/admin/produk/tambah')) ?>">+ Tambah Produk</a>
</div>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Tipe</th><th>Harga dasar</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($result['items'] as $p): ?>
        <tr>
            <td><img class="thumb" src="<?= e(upload_url('products', $p['gambar'] ?? null)) ?>" alt=""></td>
            <td><?= e($p['nama']) ?></td>
            <td><?= e($p['kategori'] ?? '-') ?></td>
            <td><?= e($p['tipe']) ?></td>
            <td><?= e(rupiah($p['harga_dasar'])) ?></td>
            <td><?= $p['aktif'] ? 'Aktif' : 'Nonaktif' ?></td>
            <td class="actions">
                <a href="<?= e(url('/admin/produk/' . (int) $p['id'] . '/ubah')) ?>">Ubah</a>
                <form method="post" action="<?= e(url('/admin/produk/' . (int) $p['id'] . '/hapus')) ?>" class="inline"
                      onsubmit="return confirm('Hapus produk ini?')">
                    <?= csrf_field() ?>
                    <button class="link danger" type="submit">Hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$result['items']): ?><tr><td colspan="7" class="muted">Belum ada produk.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($result['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a class="<?= $i === $result['page'] ? 'active' : '' ?>" href="<?= e(url('/admin/produk?' . http_build_query(array_filter(['q' => $q, 'halaman' => $i])))) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
