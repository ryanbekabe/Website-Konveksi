<?php
/** @var array $r @var array $presets @var string $path @var array $exports  [label => query] */
$qs = fn(array $extra = []) => http_build_query(['dari' => $r['dari'], 'sampai' => $r['sampai']] + $extra);
?>
<form method="get" class="filters no-print">
    <label class="check">Dari <input type="date" name="dari" value="<?= e($r['dari']) ?>" required></label>
    <label class="check">Sampai <input type="date" name="sampai" value="<?= e($r['sampai']) ?>" required></label>
    <button class="btn" type="submit">Tampilkan</button>
    <span class="presets">
        <?php foreach ($presets as $label => [$a, $b]): ?>
            <a href="<?= e(url($path . '?' . http_build_query(['dari' => $a, 'sampai' => $b]))) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </span>
</form>
<p class="muted">Periode <?= e(date('d M Y', strtotime($r['dari']))) ?> &ndash; <?= e(date('d M Y', strtotime($r['sampai']))) ?> (<?= (int) $r['days'] ?> hari)</p>
<p class="no-print export-links">
    <button type="button" class="btn btn-sm btn-outline" onclick="window.print()">Cetak</button>
    <?php foreach ($exports as $label => $extra): ?>
        <a class="btn btn-sm btn-outline" href="<?= e(url($path . '?' . $qs($extra + ['format' => 'csv']))) ?>">CSV <?= e($label) ?> (koma)</a>
        <a class="btn btn-sm btn-outline" href="<?= e(url($path . '?' . $qs($extra + ['format' => 'csv', 'sep' => ';']))) ?>">CSV <?= e($label) ?> (titik koma, untuk Excel Indonesia)</a>
    <?php endforeach; ?>
</p>
