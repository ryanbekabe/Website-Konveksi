<div class="cols-3">
    <?php if ($canSales): ?>
    <a class="panel report-card" href="<?= e(url('/admin/laporan/penjualan')) ?>">
        <h2>Penjualan</h2>
        <p class="muted">Omzet, kas masuk, piutang, tren per hari/bulan, produk terlaris, pelanggan teratas, dan daftar pesanan.</p>
    </a>
    <?php endif; ?>
    <?php if ($canProd): ?>
    <a class="panel report-card" href="<?= e(url('/admin/laporan/produksi')) ?>">
        <h2>Produksi</h2>
        <p class="muted">Pesanan selesai produksi, ketepatan waktu, rata-rata durasi per tahap dan per petugas, serta pesanan yang sedang berjalan.</p>
    </a>
    <?php endif; ?>
    <a class="panel report-card" href="<?= e(url('/admin/laporan/stok')) ?>">
        <h2>Stok Bahan Baku</h2>
        <p class="muted">Posisi stok saat ini, bahan menipis, stok masuk dan keluar pada periode, serta pemakaian per pesanan.</p>
    </a>
</div>
<p class="muted">Semua laporan dapat dicetak dan diunduh sebagai CSV.</p>
