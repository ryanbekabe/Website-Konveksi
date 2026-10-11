<?php if ($lowCount && !$menipis): ?>
    <div class="alert alert-error"><?= (int) $lowCount ?> bahan di bawah stok minimum. <a href="<?= e(url('/admin/bahan?menipis=1')) ?>">Tampilkan</a></div>
<?php endif; ?>
<div class="toolbar">
    <form method="get" class="filters">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari bahan...">
        <select name="supplier">
            <option value="">Semua supplier</option>
            <?php foreach ($suppliers as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= $supplierId === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="check"><input type="checkbox" name="menipis" value="1" <?= $menipis ? 'checked' : '' ?>> Hanya yang menipis</label>
        <button class="btn" type="submit">Filter</button>
    </form>
    <?php if (Auth::hasRole(['admin'])): ?><a class="btn" href="<?= e(url('/admin/bahan/tambah')) ?>">+ Tambah Bahan</a><?php endif; ?>
</div>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Bahan</th><th>Stok</th><th>Minimum</th><th>Supplier</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($result['items'] as $m): ?>
        <tr>
            <td><a href="<?= e(url('/admin/bahan/' . (int) $m['id'])) ?>"><?= e($m['nama']) ?></a></td>
            <td><strong><?= e(Material::fmt((float) $m['stok'])) ?></strong> <?= e($m['satuan']) ?>
                <?= $m['menipis'] ? '<span class="badge badge-danger">Menipis</span>' : '' ?></td>
            <td><?= e(Material::fmt((float) $m['stok_minimum'])) ?> <?= e($m['satuan']) ?></td>
            <td><?= e($m['supplier'] ?? '-') ?></td>
            <td><a href="<?= e(url('/admin/bahan/' . (int) $m['id'])) ?>">Kelola</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$result['items']): ?><tr><td colspan="5" class="muted">Tidak ada bahan.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<p class="muted"><?= (int) $result['total'] ?> bahan</p>

<?php if ($result['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a class="<?= $i === $result['page'] ? 'active' : '' ?>" href="<?= e(url('/admin/bahan?' . http_build_query(array_filter(['q' => $q, 'supplier' => $supplierId, 'menipis' => $menipis ? 1 : 0, 'halaman' => $i])))) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
