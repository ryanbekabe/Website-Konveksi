<?php

/**
 * Laporan penjualan, produksi, dan stok. Hak akses per laporan diatur di app/routes.php.
 * Tambahkan ?format=csv (&jenis=..., &sep=;) untuk mengunduh CSV.
 */
class AdminReportController extends Controller
{
    public function index(): void
    {
        $this->view('admin/reports/index', [
            'title'      => 'Laporan',
            'canSales'   => Auth::hasRole(['admin', 'keuangan']),
            'canProd'    => Auth::hasRole(['admin', 'produksi']),
        ], 'admin');
    }

    public function sales(): void
    {
        $r = $this->range();
        if ($this->wantsCsv()) {
            if ($this->input('jenis') === 'produk') {
                $this->csv("laporan-penjualan-produk_{$r['dari']}_{$r['sampai']}.csv",
                    ['Produk', 'Jumlah (pcs)', 'Omzet (Rp)', 'Jumlah pesanan'],
                    array_map(fn($x) => [$x['nama'], (int) $x['qty'], (float) $x['omzet'], (int) $x['pesanan']], $this->allProducts($r)));
            }
            $this->csv("laporan-penjualan_{$r['dari']}_{$r['sampai']}.csv",
                ['Kode pesanan', 'Tanggal', 'Pelanggan', 'Status', 'Subtotal (Rp)', 'Ongkir (Rp)', 'Diskon (Rp)', 'Total (Rp)', 'Terbayar (Rp)', 'Sisa (Rp)'],
                array_map(fn($o) => [$o['kode_order'], $o['created_at'], $o['pelanggan'], Order::label($o['status']),
                    (float) $o['subtotal'], (float) $o['ongkir'], (float) $o['diskon'], (float) $o['total'], (float) $o['terbayar'], (float) $o['sisa']],
                    Report::salesOrders($r, true)));
        }
        $this->view('admin/reports/sales', [
            'title'  => 'Laporan Penjualan',
            'r'      => $r,
            'report' => Report::sales($r),
            'orders' => Report::salesOrders($r),
            'presets' => Report::presets(),
        ], 'admin');
    }

    public function production(): void
    {
        $r = $this->range();
        if ($this->wantsCsv()) {
            if ($this->input('jenis') === 'tahap') {
                $this->csv("laporan-produksi-tahap_{$r['dari']}_{$r['sampai']}.csv",
                    ['Kode pesanan', 'Tahap', 'Petugas', 'Status', 'Mulai', 'Selesai', 'Durasi (jam)', 'Catatan'],
                    array_map(fn($s) => [$s['kode_order'], Order::stageLabel($s['tahap']), $s['petugas'], $s['status'],
                        (string) $s['mulai'], (string) $s['selesai'], $s['jam'] === null ? '' : (float) $s['jam'], (string) $s['catatan']],
                        Report::productionStages($r)));
            }
            $this->csv("laporan-produksi_{$r['dari']}_{$r['sampai']}.csv",
                ['Kode pesanan', 'Pelanggan', 'Jumlah (pcs)', 'Mulai produksi', 'Selesai produksi', 'Lama (jam)', 'Estimasi selesai', 'Tepat waktu'],
                array_map(fn($d) => [$d['kode_order'], $d['pelanggan'], $d['pcs'], (string) $d['mulai'], (string) $d['selesai'],
                    $d['lama_jam'] === null ? '' : $d['lama_jam'], (string) $d['estimasi_selesai'],
                    $d['tepat_waktu'] === null ? '' : ($d['tepat_waktu'] ? 'Ya' : 'Tidak')],
                    Report::productionDone($r, true)));
        }
        $this->view('admin/reports/production', [
            'title'   => 'Laporan Produksi',
            'r'       => $r,
            'report'  => Report::production($r),
            'presets' => Report::presets(),
        ], 'admin');
    }

    public function stock(): void
    {
        $r = $this->range();
        if ($this->wantsCsv()) {
            if ($this->input('jenis') === 'mutasi') {
                $this->csv("laporan-mutasi-stok_{$r['dari']}_{$r['sampai']}.csv",
                    ['Tanggal', 'Bahan', 'Jenis', 'Jumlah', 'Satuan', 'Pesanan', 'Keterangan'],
                    array_map(fn($m) => [$m['tanggal'], $m['bahan'], $m['tipe'] === 'masuk' ? 'Masuk' : 'Keluar', (float) $m['qty'], $m['satuan'], $m['pesanan'], (string) $m['keterangan']],
                        Report::stockMovements($r)));
            }
            $d = Report::stock($r);
            $this->csv("laporan-stok_{$r['dari']}_{$r['sampai']}.csv",
                ['Bahan', 'Satuan', 'Supplier', 'Stok saat ini', 'Stok minimum', 'Status', "Masuk ({$r['dari']} s/d {$r['sampai']})", "Keluar ({$r['dari']} s/d {$r['sampai']})"],
                array_map(fn($m) => [$m['nama'], $m['satuan'], (string) $m['supplier'], (float) $m['stok'], (float) $m['stok_minimum'],
                    $m['menipis'] ? 'Menipis' : 'Aman', (float) $m['masuk'], (float) $m['keluar']], $d['materials']));
        }
        $this->view('admin/reports/stock', [
            'title'   => 'Laporan Stok',
            'r'       => $r,
            'report'  => Report::stock($r),
            'presets' => Report::presets(),
        ], 'admin');
    }

    private function range(): array
    {
        $r = Report::range((string) $this->input('dari', ''), (string) $this->input('sampai', ''));
        if ($r['clamped']) {
            flash('error', 'Rentang dibatasi maksimal ' . Report::MAX_DAYS . ' hari. Periode dimulai dari ' . $r['dari'] . '.');
        }
        return $r;
    }

    private function wantsCsv(): bool
    {
        return $this->input('format') === 'csv';
    }

    private function csv(string $filename, array $header, array $rows): never
    {
        log_activity('ekspor_laporan', $filename);
        Csv::download($filename, $header, $rows, $this->input('sep') === ';' ? ';' : ',');
    }

    /** Semua produk (tanpa batas 10 teratas) untuk ekspor. */
    private function allProducts(array $r): array
    {
        return Database::all(
            "SELECT pr.nama, SUM(i.qty) AS qty, SUM(i.qty * i.harga_satuan) AS omzet, COUNT(DISTINCT o.id) AS pesanan
             FROM order_items i JOIN orders o ON o.id = i.order_id JOIN products pr ON pr.id = i.product_id
             WHERE o.created_at >= ? AND o.created_at < ? AND o.status <> 'dibatalkan'
             GROUP BY pr.id, pr.nama ORDER BY omzet DESC",
            [$r['start'], $r['end']]
        );
    }
}
