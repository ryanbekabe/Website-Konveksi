<div class="toolbar">
    <form method="get" class="filters">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, atau HP...">
        <select name="role">
            <option value="">Semua peran</option>
            <?php foreach (User::ROLES as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $role === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Semua status</option>
            <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button class="btn" type="submit">Filter</button>
    </form>
    <a class="btn" href="<?= e(url('/admin/pengguna/tambah')) ?>">+ Tambah Pengguna</a>
</div>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Nama</th><th>Email</th><th>HP</th><th>Peran</th><th>Status</th><th>Pesanan</th><th>Terdaftar</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($result['items'] as $u): ?>
        <tr>
            <td><?= e($u['nama']) ?><?= (int) $u['id'] === Auth::id() ? ' <small class="muted">(Anda)</small>' : '' ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['no_hp']) ?></td>
            <td><?= e(User::ROLES[$u['role']] ?? $u['role']) ?></td>
            <td><span class="badge <?= $u['status'] === 'aktif' ? 'badge-ok' : 'badge-danger' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
            <td><?= (int) $u['jumlah_pesanan'] ?></td>
            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td><a href="<?= e(url('/admin/pengguna/' . (int) $u['id'] . '/ubah')) ?>">Ubah</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$result['items']): ?><tr><td colspan="8" class="muted">Tidak ada pengguna.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<p class="muted"><?= (int) $result['total'] ?> pengguna</p>

<?php if ($result['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a class="<?= $i === $result['page'] ? 'active' : '' ?>" href="<?= e(url('/admin/pengguna?' . http_build_query(array_filter(['q' => $q, 'role' => $role, 'status' => $status, 'halaman' => $i])))) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
