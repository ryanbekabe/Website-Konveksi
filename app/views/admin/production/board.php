<?php $me = Auth::user(); ?>
<h2>Tugas Saya</h2>
<?php if (!$mine): ?>
    <p class="muted">Tidak ada tahap yang ditugaskan kepada Anda.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Pesanan</th><th>Tahap</th><th>Status</th><th>Estimasi selesai</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($mine as $t): ?>
        <tr>
            <td><a href="<?= e(url('/admin/pesanan/' . $t['kode_order'])) ?>"><?= e($t['kode_order']) ?></a></td>
            <td><?= e(Order::stageLabel($t['tahap'])) ?></td>
            <td><?= status_badge_stage($t['status']) ?></td>
            <td><?= $t['estimasi_selesai'] ? e(date('d M Y', strtotime($t['estimasi_selesai']))) : '-' ?></td>
            <td>
                <form method="post" action="<?= e(url('/admin/produksi/' . (int) $t['id'])) ?>" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="kembali" value="papan">
                    <input type="hidden" name="petugas_id" value="<?= (int) $t['petugas_id'] ?>">
                    <input type="hidden" name="catatan" value="<?= e($t['catatan']) ?>">
                    <?php if ($t['status'] === 'menunggu'): ?><button class="btn btn-sm" name="aksi" value="mulai" type="submit">Mulai</button><?php endif; ?>
                    <?php if ($t['status'] === 'berjalan'): ?><button class="btn btn-sm" name="aksi" value="selesai" type="submit">Selesai</button><?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<h2>Pesanan dalam Produksi</h2>
<?php if (!$board): ?>
    <p class="muted">Tidak ada pesanan yang sedang diproduksi.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Progres</th><th>Tahap aktif</th><th>Petugas</th><th>Estimasi</th></tr></thead>
    <tbody>
    <?php foreach ($board as $o): $p = $o['progress']; ?>
        <tr>
            <td><a href="<?= e(url('/admin/pesanan/' . $o['kode_order'])) ?>"><?= e($o['kode_order']) ?></a></td>
            <td><?= e($o['pelanggan']) ?></td>
            <td style="min-width:140px">
                <div class="progress"><span style="width:<?= (int) $p['percent'] ?>%"></span></div>
                <small class="muted"><?= (int) $p['done'] ?>/<?= (int) $p['total'] ?> tahap</small>
            </td>
            <td><?= $p['current'] ? e(Order::stageLabel($p['current']['tahap'])) . ' ' . status_badge_stage($p['current']['status']) : '-' ?></td>
            <td><?= $p['current'] ? e($p['current']['petugas'] ?? '-') : '-' ?></td>
            <td>
                <?= $o['estimasi_selesai'] ? e(date('d M Y', strtotime($o['estimasi_selesai']))) : '-' ?>
                <?php if ($o['terlambat']): ?><span class="badge badge-danger">Terlambat</span><?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
