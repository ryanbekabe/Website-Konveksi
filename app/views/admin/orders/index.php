<form method="get" class="filters">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kode atau nama pelanggan...">
    <select name="status">
        <option value="">Semua status</option>
        <?php foreach (Order::STATUS as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filter</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Kode</th><th>Pelanggan</th><th>Tipe</th><th>Status</th><th>Total</th><th>Tanggal</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($result['items'] as $o): ?>
        <tr>
            <td><?= e($o['kode_order']) ?></td>
            <td><?= e($o['pelanggan']) ?></td>
            <td><?= e($o['tipe']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td><?= e(rupiah($o['total'])) ?></td>
            <td><?= e(date('d M Y H:i', strtotime($o['created_at']))) ?></td>
            <td><a href="<?= e(url('/admin/pesanan/' . $o['kode_order'])) ?>">Kelola</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$result['items']): ?><tr><td colspan="7" class="muted">Tidak ada pesanan.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($result['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a class="<?= $i === $result['page'] ? 'active' : '' ?>" href="<?= e(url('/admin/pesanan?' . http_build_query(array_filter(['q' => $q, 'status' => $status, 'halaman' => $i])))) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
