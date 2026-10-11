<?php $m = $material; $id = (int) $m['id']; ?>
<p><a href="<?= e(url('/admin/bahan')) ?>">&larr; Semua bahan</a>
<?php if ($canEdit): ?>
    &middot; <a href="<?= e(url("/admin/bahan/$id/ubah")) ?>">Ubah data</a>
<?php endif; ?></p>

<div class="stats">
    <div class="stat <?= $m['menipis'] ? 'stat-warn' : '' ?>"><span><?= e(Material::fmt((float) $m['stok'])) ?> <?= e($m['satuan']) ?></span>Stok saat ini <?= $m['menipis'] ? '(menipis)' : '' ?></div>
    <div class="stat"><span><?= e(Material::fmt((float) $m['stok_minimum'])) ?> <?= e($m['satuan']) ?></span>Stok minimum</div>
    <div class="stat"><span><?= e($m['supplier'] ?? '-') ?></span>Supplier</div>
</div>

<?php if ($canMove): ?>
<div class="cols-2">
    <section class="panel">
        <h2>Stok Masuk / Pemakaian</h2>
        <form method="post" action="<?= e(url("/admin/bahan/$id/mutasi")) ?>" class="form">
            <?= csrf_field() ?>
            <label>Jumlah (<?= e($m['satuan']) ?>)
                <input type="number" name="qty" min="0.01" step="0.01" required>
            </label>
            <label>Tanggal
                <input type="date" name="tanggal" value="<?= e($today) ?>" max="<?= e($today) ?>" required>
            </label>
            <label>Kode pesanan (untuk pemakaian, opsional)
                <input type="text" name="kode_order" placeholder="KNV-261011-ABCD" maxlength="30">
            </label>
            <label>Keterangan
                <input type="text" name="keterangan" maxlength="200" placeholder="Contoh: Beli dari supplier / dipakai potong">
            </label>
            <div>
                <button class="btn" type="submit" name="aksi" value="masuk">Catat Stok Masuk</button>
                <button class="btn btn-outline" type="submit" name="aksi" value="keluar">Catat Pemakaian</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Koreksi Stok (Stok Opname)</h2>
        <p class="muted">Isi hasil hitung fisik. Selisihnya dicatat otomatis sebagai penyesuaian.</p>
        <form method="post" action="<?= e(url("/admin/bahan/$id/mutasi")) ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="koreksi">
            <label>Stok hasil hitung (<?= e($m['satuan']) ?>)
                <input type="number" name="stok_fisik" min="0" step="0.01" required>
            </label>
            <label>Tanggal
                <input type="date" name="tanggal" value="<?= e($today) ?>" max="<?= e($today) ?>" required>
            </label>
            <label>Keterangan
                <input type="text" name="keterangan" maxlength="150" placeholder="Contoh: opname akhir bulan">
            </label>
            <div><button class="btn btn-outline" type="submit">Koreksi Stok</button></div>
        </form>
    </section>
</div>
<?php endif; ?>

<h2>Riwayat Stok</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Jumlah</th><th>Pesanan</th><th>Keterangan</th></tr></thead>
    <tbody>
    <?php foreach ($movements['items'] as $mv): ?>
        <tr>
            <td><?= e(date('d M Y', strtotime($mv['tanggal']))) ?></td>
            <td><span class="badge <?= $mv['tipe'] === 'masuk' ? 'badge-ok' : 'badge-warn' ?>"><?= $mv['tipe'] === 'masuk' ? 'Masuk' : 'Keluar' ?></span></td>
            <td><?= $mv['tipe'] === 'masuk' ? '+' : '-' ?><?= e(Material::fmt((float) $mv['qty'])) ?> <?= e($m['satuan']) ?></td>
            <td><?php if ($mv['kode_order']): ?><a href="<?= e(url('/admin/pesanan/' . $mv['kode_order'])) ?>"><?= e($mv['kode_order']) ?></a><?php endif; ?></td>
            <td><?= e($mv['keterangan']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$movements['items']): ?><tr><td colspan="5" class="muted">Belum ada riwayat.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<?php if ($movements['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $movements['pages']; $i++): ?>
            <a class="<?= $i === $movements['page'] ? 'active' : '' ?>" href="<?= e(url("/admin/bahan/$id?halaman=$i")) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>

<?php if ($canEdit && $movements['total'] === 0): ?>
    <form method="post" action="<?= e(url("/admin/bahan/$id/hapus")) ?>" onsubmit="return confirm('Hapus bahan ini?')">
        <?= csrf_field() ?>
        <button class="link danger" type="submit">Hapus bahan (hanya bisa bila belum punya riwayat)</button>
    </form>
<?php endif; ?>
