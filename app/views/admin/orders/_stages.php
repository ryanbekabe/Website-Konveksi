<?php
/** @var array $order @var array $stages @var array $assignees @var bool $canProduction @var string $back */
$summary = ProductionStage::summarize($stages);
$editable = $canProduction && in_array($order['status'], ['produksi', 'qc'], true);
?>
<h2>Tahapan Produksi</h2>
<div class="progress" title="<?= (int) $summary['percent'] ?>%"><span style="width:<?= (int) $summary['percent'] ?>%"></span></div>
<p class="muted"><?= (int) $summary['done'] ?> dari <?= (int) $summary['total'] ?> tahap selesai<?= $summary['current'] ? ' &middot; tahap aktif: ' . e(Order::stageLabel($summary['current']['tahap'])) : '' ?></p>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Tahap</th><th>Status</th><?php if ($editable): ?><th>Petugas, catatan &amp; aksi</th><?php else: ?><th>Petugas</th><th>Waktu</th><th>Catatan</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($stages as $s): ?>
        <tr>
            <td><?= e(Order::stageLabel($s['tahap'])) ?></td>
            <td><?= status_badge_stage($s['status']) ?></td>
            <?php if ($editable): ?>
                <td class="stage-form-cell">
                    <form method="post" action="<?= e(url('/admin/produksi/' . (int) $s['id'])) ?>" class="stage-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kembali" value="<?= e($back) ?>">
                        <select name="petugas_id" <?= $s['status'] === 'selesai' ? 'disabled' : '' ?>>
                            <option value="">- petugas -</option>
                            <?php foreach ($assignees as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= (int) $s['petugas_id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="catatan" value="<?= e($s['catatan']) ?>" maxlength="500" placeholder="Catatan" <?= $s['status'] === 'selesai' ? 'readonly' : '' ?>>
                        <small class="muted"><?= $s['mulai'] ? 'Mulai ' . e(date('d M H:i', strtotime($s['mulai']))) : '' ?><?= $s['selesai'] ? ' &middot; Selesai ' . e(date('d M H:i', strtotime($s['selesai']))) : '' ?></small>
                        <span class="stage-actions">
                            <?php if ($s['status'] !== 'selesai'): ?><button class="btn btn-sm btn-outline" name="aksi" value="simpan" type="submit">Simpan</button><?php endif; ?>
                            <?php if ($s['status'] === 'menunggu'): ?><button class="btn btn-sm" name="aksi" value="mulai" type="submit">Mulai</button><?php endif; ?>
                            <?php if ($s['status'] === 'berjalan'): ?><button class="btn btn-sm" name="aksi" value="selesai" type="submit">Selesai</button><?php endif; ?>
                            <?php if ($s['status'] !== 'menunggu'): ?><button class="btn btn-sm btn-outline" name="aksi" value="reset" type="submit" onclick="return confirm('Reset tahap ini?')">Reset</button><?php endif; ?>
                        </span>
                    </form>
                </td>
            <?php else: ?>
                <td><?= e($s['petugas'] ?? '-') ?></td>
                <td><?= $s['mulai'] ? e(date('d M H:i', strtotime($s['mulai']))) : '-' ?><?= $s['selesai'] ? ' &rarr; ' . e(date('d M H:i', strtotime($s['selesai']))) : '' ?></td>
                <td><?= e($s['catatan']) ?></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
