<div class="toolbar">
    <span class="muted"><?= count($suppliers) ?> supplier</span>
    <a class="btn" href="<?= e(url('/admin/supplier/tambah')) ?>">+ Tambah Supplier</a>
</div>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Nama</th><th>Kontak</th><th>Alamat</th><th>Bahan</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($suppliers as $s): ?>
        <tr>
            <td><?= e($s['nama']) ?></td>
            <td><?= e($s['kontak']) ?></td>
            <td><?= nl2br(e($s['alamat'])) ?></td>
            <td><a href="<?= e(url('/admin/bahan?supplier=' . (int) $s['id'])) ?>"><?= (int) $s['jumlah_bahan'] ?></a></td>
            <td class="actions">
                <a href="<?= e(url('/admin/supplier/' . (int) $s['id'] . '/ubah')) ?>">Ubah</a>
                <form method="post" action="<?= e(url('/admin/supplier/' . (int) $s['id'] . '/hapus')) ?>" class="inline"
                      onsubmit="return confirm('Hapus supplier ini? Bahan terkait menjadi tanpa supplier.')">
                    <?= csrf_field() ?>
                    <button class="link danger" type="submit">Hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$suppliers): ?><tr><td colspan="5" class="muted">Belum ada supplier.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
