<?php

/**
 * Tahapan produksi per pesanan (pemotongan -> ... -> packing).
 * Aturan: tahap dimulai berurutan, hanya saat pesanan berstatus produksi/qc.
 * Memulai tahap 'qc' mengubah status pesanan menjadi 'qc'; selesainya tahap terakhir
 * mengubahnya menjadi 'menunggu_pelunasan'.
 */
class ProductionStage
{
    public const STATUS = ['menunggu' => 'Menunggu', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'];

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM production_stages WHERE id = ?', [$id]);
    }

    public static function forOrder(int $orderId): array
    {
        return Database::all(
            'SELECT s.*, u.nama AS petugas FROM production_stages s
             LEFT JOIN users u ON u.id = s.petugas_id WHERE s.order_id = ? ORDER BY s.id',
            [$orderId]
        );
    }

    /** Pengguna yang dapat ditugaskan (staf produksi dan admin aktif). */
    public static function assignees(): array
    {
        return Database::all("SELECT id, nama, role FROM users WHERE role IN ('produksi','admin') AND status = 'aktif' ORDER BY nama");
    }

    /** Ringkasan progres: selesai / total dan tahap aktif. */
    public static function summarize(array $stages): array
    {
        $done = 0;
        $current = null;
        foreach ($stages as $s) {
            if ($s['status'] === 'selesai') {
                $done++;
            } elseif ($current === null) {
                $current = $s;
            }
        }
        $total = count($stages);
        return ['done' => $done, 'total' => $total, 'percent' => $total ? (int) round($done / $total * 100) : 0, 'current' => $current];
    }

    /** Pesanan yang sedang diproduksi beserta tahapannya, untuk papan produksi. */
    public static function board(): array
    {
        $orders = Database::all(
            "SELECT o.id, o.kode_order, o.status, o.estimasi_selesai, u.nama AS pelanggan,
                    (o.estimasi_selesai IS NOT NULL AND o.estimasi_selesai < CURDATE()) AS terlambat
             FROM orders o JOIN users u ON u.id = o.user_id
             WHERE o.status IN ('produksi','qc')
             ORDER BY (o.estimasi_selesai IS NULL), o.estimasi_selesai, o.id"
        );
        if (!$orders) {
            return [];
        }
        $ids = array_column($orders, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stages = Database::all(
            "SELECT s.*, u.nama AS petugas FROM production_stages s LEFT JOIN users u ON u.id = s.petugas_id
             WHERE s.order_id IN ($in) ORDER BY s.id",
            $ids
        );
        $byOrder = [];
        foreach ($stages as $s) {
            $byOrder[$s['order_id']][] = $s;
        }
        foreach ($orders as &$o) {
            $o['stages'] = $byOrder[$o['id']] ?? [];
            $o['progress'] = self::summarize($o['stages']);
        }
        return $orders;
    }

    /** Tahap yang ditugaskan ke pengguna dan belum selesai. */
    public static function assignedTo(int $userId): array
    {
        return Database::all(
            "SELECT s.*, o.kode_order, o.estimasi_selesai FROM production_stages s
             JOIN orders o ON o.id = s.order_id
             WHERE s.petugas_id = ? AND s.status <> 'selesai' AND o.status IN ('produksi','qc')
             ORDER BY (o.estimasi_selesai IS NULL), o.estimasi_selesai, s.id",
            [$userId]
        );
    }

    public static function countActive(): array
    {
        return [
            'produksi'  => (int) Database::value("SELECT COUNT(*) FROM orders WHERE status IN ('produksi','qc')"),
            'terlambat' => (int) Database::value(
                "SELECT COUNT(*) FROM orders WHERE estimasi_selesai < CURDATE()
                 AND status NOT IN ('dikirim','selesai','dibatalkan')"
            ),
        ];
    }

    /* ---------- Aksi ---------- */

    /**
     * Terapkan aksi ke satu tahap. Melempar DomainException (pesan siap tampil) bila tidak diizinkan.
     * @return string pesan sukses
     */
    public static function apply(int $stageId, string $aksi, ?int $petugasId, string $catatan, array $actor): string
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Kunci baris pesanan agar dua staf tidak mengubah status bersamaan.
            $stage = Database::one('SELECT * FROM production_stages WHERE id = ?', [$stageId]);
            if (!$stage) {
                throw new DomainException('Tahap tidak ditemukan.');
            }
            $order = Database::one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$stage['order_id']]);
            $stage = Database::one('SELECT * FROM production_stages WHERE id = ?', [$stageId]);
            if (!in_array($order['status'], ['produksi', 'qc'], true)) {
                throw new DomainException('Tahapan hanya dapat diubah saat pesanan berstatus Dalam produksi atau Quality control.');
            }
            $all = self::forOrder((int) $order['id']);
            $idx = array_search($stageId, array_column($all, 'id'));
            $label = Order::stageLabel($stage['tahap']);

            if ($petugasId !== null && !Database::value("SELECT id FROM users WHERE id = ? AND role IN ('produksi','admin') AND status = 'aktif'", [$petugasId])) {
                throw new DomainException('Petugas tidak valid.');
            }
            $catatan = mb_substr($catatan, 0, 500);

            switch ($aksi) {
                case 'simpan':
                    if ($stage['status'] === 'selesai') {
                        throw new DomainException('Tahap yang sudah selesai tidak dapat diubah. Gunakan Reset bila perlu.');
                    }
                    Database::update('production_stages', ['petugas_id' => $petugasId, 'catatan' => $catatan], 'id = ?', [$stageId]);
                    $msg = "Tahap $label disimpan.";
                    break;

                case 'mulai':
                    if ($stage['status'] !== 'menunggu') {
                        throw new DomainException('Tahap ini sudah dimulai.');
                    }
                    if ($idx > 0 && $all[$idx - 1]['status'] !== 'selesai') {
                        throw new DomainException('Selesaikan tahap ' . Order::stageLabel($all[$idx - 1]['tahap']) . ' terlebih dahulu.');
                    }
                    if ($petugasId === null) {
                        if ($actor['role'] === 'produksi') {
                            $petugasId = (int) $actor['id']; // staf produksi yang memulai otomatis menjadi petugas
                        } else {
                            throw new DomainException('Pilih petugas sebelum memulai tahap.');
                        }
                    }
                    Database::update('production_stages', [
                        'petugas_id' => $petugasId, 'catatan' => $catatan, 'status' => 'berjalan', 'mulai' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$stageId]);
                    if ($stage['tahap'] === 'qc' && $order['status'] === 'produksi') {
                        Order::setStatus((int) $order['id'], 'qc');
                        notify((int) $order['user_id'], "Pesanan {$order['kode_order']} masuk tahap quality control.");
                    }
                    $msg = "Tahap $label dimulai.";
                    break;

                case 'selesai':
                    if ($stage['status'] !== 'berjalan') {
                        throw new DomainException('Mulai tahap ini terlebih dahulu.');
                    }
                    Database::update('production_stages', [
                        'status' => 'selesai', 'selesai' => date('Y-m-d H:i:s'), 'catatan' => $catatan,
                    ], 'id = ?', [$stageId]);
                    $msg = "Tahap $label selesai.";
                    $remaining = (int) Database::value("SELECT COUNT(*) FROM production_stages WHERE order_id = ? AND status <> 'selesai'", [$order['id']]);
                    if ($remaining === 0) {
                        Order::setStatus((int) $order['id'], 'menunggu_pelunasan');
                        $fresh = Order::findByCode($order['kode_order']);
                        $next = Order::nextPayment($fresh);
                        notify((int) $order['user_id'], "Produksi pesanan {$order['kode_order']} selesai." . ($next ? ' Silakan lunasi ' . rupiah($next['jumlah']) . '.' : ''));
                        $msg .= ' Semua tahap selesai, pesanan menunggu pelunasan.';
                    } else {
                        $done = count($all) - $remaining;
                        notify((int) $order['user_id'], "Pesanan {$order['kode_order']}: tahap $label selesai ($done/" . count($all) . ').');
                    }
                    break;

                case 'reset':
                    if ($stage['status'] === 'menunggu') {
                        throw new DomainException('Tahap ini belum dimulai.');
                    }
                    foreach (array_slice($all, $idx + 1) as $later) {
                        if ($later['status'] !== 'menunggu') {
                            throw new DomainException('Reset tahap setelahnya terlebih dahulu.');
                        }
                    }
                    Database::update('production_stages', ['status' => 'menunggu', 'mulai' => null, 'selesai' => null, 'catatan' => $catatan], 'id = ?', [$stageId]);
                    if ($stage['tahap'] === 'qc' && $order['status'] === 'qc') {
                        Order::setStatus((int) $order['id'], 'produksi');
                    }
                    $msg = "Tahap $label di-reset.";
                    break;

                default:
                    throw new DomainException('Aksi tidak dikenal.');
            }

            Database::insert('activity_logs', [
                'user_id' => $actor['id'], 'aksi' => 'produksi_' . $aksi, 'objek' => $order['kode_order'],
                'detail' => $stage['tahap'], 'ip' => client_ip(),
            ]);
            $pdo->commit();
            return $msg;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
