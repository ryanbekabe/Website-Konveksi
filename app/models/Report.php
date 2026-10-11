<?php

/**
 * Kueri laporan (hanya baca). Semua rentang tanggal inklusif: $dari 00:00:00 sampai $sampai 23:59:59.
 * Penjualan memakai tanggal pesanan dibuat dan mengecualikan pesanan dibatalkan (kecuali disebut lain).
 */
class Report
{
    public const MAX_DAYS = 366;
    private const MAX_DETAIL = 500;      // baris detail di layar
    private const MAX_EXPORT = 20000;    // baris maksimum pada ekspor CSV

    /**
     * Normalisasi rentang dari input; default bulan berjalan.
     * @return array{dari: string, sampai: string, start: string, end: string, days: int, clamped: bool}
     */
    public static function range(?string $dari, ?string $sampai): array
    {
        $valid = fn(?string $d) => $d && ($x = DateTime::createFromFormat('Y-m-d', $d)) && $x->format('Y-m-d') === $d ? $d : null;
        $today = date('Y-m-d');
        $dari = $valid($dari) ?? date('Y-m-01');
        $sampai = $valid($sampai) ?? $today;
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        $clamped = false;
        $days = (int) ((strtotime($sampai) - strtotime($dari)) / 86400) + 1;
        if ($days > self::MAX_DAYS) {
            $dari = date('Y-m-d', strtotime($sampai . ' -' . (self::MAX_DAYS - 1) . ' days'));
            $days = self::MAX_DAYS;
            $clamped = true;
        }
        return [
            'dari'    => $dari,
            'sampai'  => $sampai,
            'start'   => $dari . ' 00:00:00',
            'end'     => date('Y-m-d', strtotime($sampai . ' +1 day')) . ' 00:00:00', // eksklusif
            'days'    => $days,
            'clamped' => $clamped,
        ];
    }

    /** Pilihan cepat rentang tanggal untuk tautan. */
    public static function presets(): array
    {
        $today = date('Y-m-d');
        return [
            'Bulan ini'     => [date('Y-m-01'), $today],
            'Bulan lalu'    => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
            '30 hari'       => [date('Y-m-d', strtotime('-29 days')), $today],
            'Tahun ini'     => [date('Y-01-01'), $today],
        ];
    }

    /* ------------------------------ PENJUALAN ------------------------------ */

    public static function sales(array $r): array
    {
        $p = [$r['start'], $r['end']];
        $base = "o.created_at >= ? AND o.created_at < ? AND o.status <> 'dibatalkan'";

        $sum = Database::one(
            "SELECT COUNT(*) AS pesanan, COALESCE(SUM(o.subtotal),0) AS subtotal, COALESCE(SUM(o.ongkir),0) AS ongkir,
                    COALESCE(SUM(o.diskon),0) AS diskon, COALESCE(SUM(o.total),0) AS omzet,
                    COALESCE(AVG(o.total),0) AS rata_rata
             FROM orders o WHERE $base", $p
        );
        $sum['pcs'] = (int) Database::value(
            "SELECT COALESCE(SUM(i.qty),0) FROM order_items i JOIN orders o ON o.id = i.order_id WHERE $base", $p
        );
        $sum['dibatalkan'] = (int) Database::value(
            "SELECT COUNT(*) FROM orders o WHERE o.created_at >= ? AND o.created_at < ? AND o.status = 'dibatalkan'", $p
        );
        // Kas masuk: pembayaran terverifikasi pada rentang (berdasarkan tanggal verifikasi/bayar).
        $sum['kas_masuk'] = (float) Database::value(
            "SELECT COALESCE(SUM(jumlah),0) FROM payments WHERE status = 'terverifikasi' AND dibayar_pada >= ? AND dibayar_pada < ?", $p
        );
        // Piutang: sisa tagihan pesanan pada rentang yang belum lunas.
        $sum['piutang'] = (float) Database::value(
            "SELECT COALESCE(SUM(GREATEST(o.total - COALESCE(pay.paid,0), 0)),0)
             FROM orders o LEFT JOIN (SELECT order_id, SUM(jumlah) AS paid FROM payments WHERE status = 'terverifikasi' GROUP BY order_id) pay ON pay.order_id = o.id
             WHERE $base", $p
        );

        // Per hari; bila rentang panjang, per bulan.
        $monthly = $r['days'] > 62;
        $fmt = $monthly ? '%Y-%m' : '%Y-%m-%d';
        $periode = Database::all(
            "SELECT DATE_FORMAT(o.created_at, '$fmt') AS periode, COUNT(*) AS pesanan, SUM(o.total) AS omzet
             FROM orders o WHERE $base GROUP BY periode ORDER BY periode", $p
        );

        $produk = Database::all(
            "SELECT pr.nama, SUM(i.qty) AS qty, SUM(i.qty * i.harga_satuan) AS omzet, COUNT(DISTINCT o.id) AS pesanan
             FROM order_items i JOIN orders o ON o.id = i.order_id JOIN products pr ON pr.id = i.product_id
             WHERE $base GROUP BY pr.id, pr.nama ORDER BY omzet DESC LIMIT 10", $p
        );
        $pelanggan = Database::all(
            "SELECT u.nama, COUNT(*) AS pesanan, SUM(o.total) AS omzet
             FROM orders o JOIN users u ON u.id = o.user_id WHERE $base GROUP BY u.id, u.nama ORDER BY omzet DESC LIMIT 10", $p
        );
        $status = Database::all(
            "SELECT o.status, COUNT(*) AS pesanan, SUM(o.total) AS omzet
             FROM orders o WHERE o.created_at >= ? AND o.created_at < ? GROUP BY o.status ORDER BY pesanan DESC", $p
        );

        return compact('sum', 'periode', 'produk', 'pelanggan', 'status', 'monthly');
    }

    /** Detail pesanan penjualan. @return array rows (tiap baris array asosiatif) */
    public static function salesOrders(array $r, bool $export = false): array
    {
        return Database::all(
            "SELECT o.kode_order, o.created_at, u.nama AS pelanggan, o.status, o.subtotal, o.ongkir, o.diskon, o.total,
                    COALESCE(pay.paid,0) AS terbayar, GREATEST(o.total - COALESCE(pay.paid,0),0) AS sisa
             FROM orders o JOIN users u ON u.id = o.user_id
             LEFT JOIN (SELECT order_id, SUM(jumlah) AS paid FROM payments WHERE status = 'terverifikasi' GROUP BY order_id) pay ON pay.order_id = o.id
             WHERE o.created_at >= ? AND o.created_at < ? AND o.status <> 'dibatalkan'
             ORDER BY o.id DESC LIMIT " . ($export ? self::MAX_EXPORT : self::MAX_DETAIL),
            [$r['start'], $r['end']]
        );
    }

    /* ------------------------------ PRODUKSI ------------------------------ */

    public static function production(array $r): array
    {
        $p = [$r['start'], $r['end']];

        $sum = [
            'mulai' => (int) Database::value(
                "SELECT COUNT(DISTINCT order_id) FROM production_stages WHERE mulai >= ? AND mulai < ?", $p),
            'wip' => (int) Database::value("SELECT COUNT(*) FROM orders WHERE status IN ('produksi','qc')"),
            'wip_terlambat' => (int) Database::value(
                "SELECT COUNT(*) FROM orders WHERE status IN ('produksi','qc') AND estimasi_selesai IS NOT NULL AND estimasi_selesai < CURDATE()"),
        ];

        $done = self::productionDone($r, false);
        $sum['selesai'] = count($done);
        $sum['pcs'] = array_sum(array_column($done, 'pcs'));
        $withEst = array_filter($done, fn($d) => $d['estimasi_selesai'] !== null);
        $onTime = array_filter($withEst, fn($d) => $d['tepat_waktu'] === 1);
        $sum['dengan_estimasi'] = count($withEst);
        $sum['tepat_waktu'] = count($onTime);
        $sum['persen_tepat'] = $withEst ? round(count($onTime) / count($withEst) * 100) : null;
        $durations = array_filter(array_column($done, 'lama_jam'), fn($v) => $v !== null);
        $sum['rata_hari'] = $durations ? round(array_sum($durations) / count($durations) / 24, 1) : null;

        $tahap = Database::all(
            "SELECT tahap, COUNT(*) AS selesai, AVG(TIMESTAMPDIFF(MINUTE, mulai, selesai)) / 60 AS rata_jam
             FROM production_stages WHERE status = 'selesai' AND selesai >= ? AND selesai < ? AND mulai IS NOT NULL
             GROUP BY tahap", $p
        );
        // Urutkan sesuai alur produksi.
        $order = array_flip(Order::STAGES);
        usort($tahap, fn($a, $b) => ($order[$a['tahap']] ?? 99) <=> ($order[$b['tahap']] ?? 99));

        $petugas = Database::all(
            "SELECT COALESCE(u.nama, '(tanpa petugas)') AS nama, COUNT(*) AS selesai,
                    AVG(TIMESTAMPDIFF(MINUTE, s.mulai, s.selesai)) / 60 AS rata_jam
             FROM production_stages s LEFT JOIN users u ON u.id = s.petugas_id
             WHERE s.status = 'selesai' AND s.selesai >= ? AND s.selesai < ? AND s.mulai IS NOT NULL
             GROUP BY s.petugas_id, u.nama ORDER BY selesai DESC", $p
        );

        return compact('sum', 'tahap', 'petugas', 'done');
    }

    /**
     * Pesanan yang tahap terakhirnya (packing) selesai pada rentang.
     * lama_jam = dari mulai tahap pertama sampai packing selesai.
     */
    public static function productionDone(array $r, bool $export): array
    {
        $rows = Database::all(
            "SELECT o.kode_order, u.nama AS pelanggan, o.estimasi_selesai,
                    (SELECT COALESCE(SUM(i.qty),0) FROM order_items i WHERE i.order_id = o.id) AS pcs,
                    (SELECT MIN(s2.mulai) FROM production_stages s2 WHERE s2.order_id = o.id) AS mulai,
                    pk.selesai AS selesai
             FROM production_stages pk JOIN orders o ON o.id = pk.order_id JOIN users u ON u.id = o.user_id
             WHERE pk.tahap = 'packing' AND pk.status = 'selesai' AND pk.selesai >= ? AND pk.selesai < ?
             ORDER BY pk.selesai DESC LIMIT " . ($export ? self::MAX_EXPORT : self::MAX_DETAIL),
            [$r['start'], $r['end']]
        );
        foreach ($rows as &$d) {
            $d['pcs'] = (int) $d['pcs'];
            $d['lama_jam'] = $d['mulai'] ? round((strtotime($d['selesai']) - strtotime($d['mulai'])) / 3600, 1) : null;
            $d['tepat_waktu'] = $d['estimasi_selesai'] === null ? null : (substr($d['selesai'], 0, 10) <= $d['estimasi_selesai'] ? 1 : 0);
        }
        return $rows;
    }

    public static function productionStages(array $r): array
    {
        return Database::all(
            "SELECT o.kode_order, s.tahap, COALESCE(u.nama,'') AS petugas, s.status, s.mulai, s.selesai,
                    ROUND(TIMESTAMPDIFF(MINUTE, s.mulai, s.selesai) / 60, 2) AS jam, s.catatan
             FROM production_stages s JOIN orders o ON o.id = s.order_id LEFT JOIN users u ON u.id = s.petugas_id
             WHERE (s.selesai >= ? AND s.selesai < ?) OR (s.mulai >= ? AND s.mulai < ?)
             ORDER BY o.id, s.id LIMIT " . self::MAX_EXPORT,
            [$r['start'], $r['end'], $r['start'], $r['end']]
        );
    }

    /* ------------------------------- STOK ------------------------------- */

    public static function stock(array $r): array
    {
        $materials = Database::all(
            "SELECT m.id, m.nama, m.satuan, s.nama AS supplier, m.stok, m.stok_minimum, (m.stok <= m.stok_minimum) AS menipis,
                    COALESCE(mm.masuk,0) AS masuk, COALESCE(mm.keluar,0) AS keluar
             FROM materials m LEFT JOIN suppliers s ON s.id = m.supplier_id
             LEFT JOIN (SELECT material_id,
                               SUM(CASE WHEN tipe = 'masuk' THEN qty ELSE 0 END) AS masuk,
                               SUM(CASE WHEN tipe = 'keluar' THEN qty ELSE 0 END) AS keluar
                        FROM material_movements WHERE tanggal BETWEEN ? AND ? GROUP BY material_id) mm ON mm.material_id = m.id
             ORDER BY menipis DESC, m.nama",
            [$r['dari'], $r['sampai']]
        );
        $sum = [
            'bahan'   => count($materials),
            'menipis' => count(array_filter($materials, fn($m) => $m['menipis'])),
            'mutasi'  => (int) Database::value('SELECT COUNT(*) FROM material_movements WHERE tanggal BETWEEN ? AND ?', [$r['dari'], $r['sampai']]),
        ];
        $pemakaian = Database::all(
            "SELECT o.kode_order, COUNT(*) AS item, GROUP_CONCAT(CONCAT(m.nama, ' ', TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM mm.qty)), ' ', m.satuan) ORDER BY mm.id SEPARATOR '; ') AS rincian
             FROM material_movements mm JOIN orders o ON o.id = mm.order_id JOIN materials m ON m.id = mm.material_id
             WHERE mm.tipe = 'keluar' AND mm.tanggal BETWEEN ? AND ?
             GROUP BY o.id, o.kode_order ORDER BY o.id DESC LIMIT 50",
            [$r['dari'], $r['sampai']]
        );
        return compact('materials', 'sum', 'pemakaian');
    }

    public static function stockMovements(array $r): array
    {
        return Database::all(
            "SELECT mm.tanggal, m.nama AS bahan, mm.tipe, mm.qty, m.satuan, COALESCE(o.kode_order,'') AS pesanan, mm.keterangan
             FROM material_movements mm JOIN materials m ON m.id = mm.material_id LEFT JOIN orders o ON o.id = mm.order_id
             WHERE mm.tanggal BETWEEN ? AND ? ORDER BY mm.tanggal, mm.id LIMIT " . self::MAX_EXPORT,
            [$r['dari'], $r['sampai']]
        );
    }
}
