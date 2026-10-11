<?php $totalQty = 0; ?>
<div class="doc">
    <div class="head">
        <div>
            <h1>SURAT PERINTAH KERJA</h1>
            <div class="biz"><strong><?= e($usaha['nama']) ?></strong> &middot; Dokumen internal produksi</div>
        </div>
        <div class="meta">
            <strong><?= e($order['kode_order']) ?></strong><br>
            Tgl pesan: <?= e(date('d M Y', strtotime($order['created_at']))) ?><br>
            <strong>Estimasi selesai: <?= $order['estimasi_selesai'] ? e(date('d M Y', strtotime($order['estimasi_selesai']))) : '........................' ?></strong><br>
            Status: <?= e(Order::label($order['status'])) ?>
        </div>
    </div>

    <div class="cols">
        <div>
            <h3>Pelanggan</h3>
            <strong><?= e($order['pelanggan']) ?></strong><br><?= e($order['pelanggan_hp']) ?>
        </div>
        <div>
            <h3>Tipe pesanan</h3>
            <?= e($order['tipe'] === 'custom' ? 'Custom (sablon/bordir)' : 'Ready stock') ?>
        </div>
    </div>

    <h3>Rincian produksi (jumlah per ukuran)</h3>
    <table>
        <thead>
            <tr>
                <th>Produk</th><th>Warna</th>
                <?php foreach ($columns as $c): ?><th class="c"><?= e($c) ?></th><?php endforeach; ?>
                <th class="c">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $g): $rowTotal = array_sum($g['sizes']); $totalQty += $rowTotal; ?>
            <tr>
                <td><?= e($g['nama']) ?></td>
                <td><?= e($g['warna']) ?></td>
                <?php foreach ($columns as $c): ?><td class="c"><?= (int) ($g['sizes'][$c] ?? 0) ?: '-' ?></td><?php endforeach; ?>
                <td class="c"><strong><?= $rowTotal ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="<?= 2 + count($columns) ?>" class="num">Total pcs</th><th class="c"><?= $totalQty ?></th></tr></tfoot>
    </table>

    <h3>Catatan &amp; instruksi pelanggan</h3>
    <div class="note"><?= $order['catatan'] !== null && trim($order['catatan']) !== '' ? e($order['catatan']) : '(tidak ada catatan)' ?></div>

    <h3>File desain / mockup</h3>
    <?php if (!$designs): ?>
        <p class="muted">Tidak ada file desain.</p>
    <?php else: ?>
        <p>
        <?php foreach ($designs as $d): ?>
            <?php if (str_ends_with($d['file'], '.pdf')): ?>
                <a href="<?= e(url('/berkas/desain/' . (int) $d['id'])) ?>"><?= e($d['tipe']) ?> #<?= (int) $d['id'] ?> (PDF)</a>
            <?php else: ?>
                <figure style="display:inline-block;margin:0 8px 8px 0">
                    <img class="design-img" src="<?= e(url('/berkas/desain/' . (int) $d['id'])) ?>" alt="<?= e($d['tipe']) ?>">
                    <figcaption><small><?= e($d['tipe']) ?> #<?= (int) $d['id'] ?><?= $d['tipe'] === 'mockup' ? ' - ' . e($d['status_approval']) : '' ?></small></figcaption>
                </figure>
            <?php endif; ?>
        <?php endforeach; ?>
        </p>
    <?php endif; ?>

    <h3>Checklist tahapan</h3>
    <table>
        <thead><tr><th style="width:28px"></th><th>Tahap</th><th>Petugas</th><th>Mulai</th><th>Selesai</th><th>Paraf</th></tr></thead>
        <tbody>
        <?php foreach ($stages as $s): ?>
            <tr style="height:30px">
                <td class="c"><span class="check"></span></td>
                <td><?= e(Order::stageLabel($s['tahap'])) ?></td>
                <td></td><td></td><td></td><td></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="sign">
        <div>Dibuat oleh (Admin)</div>
        <div>Kepala Produksi</div>
        <div>Quality Control</div>
    </div>
</div>
