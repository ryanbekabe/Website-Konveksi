<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Dokumen') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
</head>
<body>
<div class="no-print toolbar-print">
    <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    <?php if (!empty($back)): ?><a href="<?= e(url($back)) ?>">&larr; Kembali</a><?php endif; ?>
</div>
<?= $content ?>
</body>
</html>
