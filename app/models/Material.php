<?php

/**
 * Bahan baku. Kolom `stok` hanya diubah lewat mutasi (move/adjust) agar selalu sesuai riwayat.
 */
class Material
{
    public const PER_PAGE = 20;
    public const UNITS = ['kg', 'gram', 'meter', 'yard', 'roll', 'pcs', 'lusin', 'cone', 'liter', 'botol', 'kaleng'];

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT m.*, s.nama AS supplier, (m.stok <= m.stok_minimum) AS menipis
             FROM materials m LEFT JOIN suppliers s ON s.id = m.supplier_id WHERE m.id = ?',
            [$id]
        );
    }

    /** @return array{items: array, total: int, page: int, pages: int} */
    public static function paginate(string $q, int $supplierId, bool $menipis, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = 'm.nama LIKE ?';
            $params[] = '%' . addcslashes($q, '%_\\') . '%';
        }
        if ($supplierId > 0) {
            $where[] = 'm.supplier_id = ?';
            $params[] = $supplierId;
        }
        if ($menipis) {
            $where[] = 'm.stok <= m.stok_minimum';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) FROM materials m WHERE $whereSql", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $items = Database::all(
            "SELECT m.*, s.nama AS supplier, (m.stok <= m.stok_minimum) AS menipis
             FROM materials m LEFT JOIN suppliers s ON s.id = m.supplier_id
             WHERE $whereSql ORDER BY (m.stok <= m.stok_minimum) DESC, m.nama LIMIT " . self::PER_PAGE . ' OFFSET ' . (int) $offset,
            $params
        );
        return compact('items', 'total', 'page', 'pages');
    }

    public static function countLow(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM materials WHERE stok <= stok_minimum');
    }

    public static function hasMovements(int $id): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM material_movements WHERE material_id = ?', [$id]);
    }

    /** Buat bahan; stok awal (bila > 0) dicatat sebagai mutasi masuk. */
    public static function create(array $data, float $initialStock): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $id = Database::insert('materials', $data + ['stok' => 0]);
            if ($initialStock > 0) {
                self::applyMove($id, 'masuk', $initialStock, date('Y-m-d'), 'Stok awal', null);
            }
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data): void
    {
        Database::update('materials', $data, 'id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        if (self::hasMovements($id)) {
            throw new DomainException('Bahan yang sudah punya riwayat stok tidak dapat dihapus.');
        }
        Database::delete('materials', 'id = ?', [$id]);
    }

    /**
     * Catat mutasi stok (masuk/keluar) secara atomik. Stok tidak boleh negatif.
     * @throws DomainException bila stok tidak cukup atau data tidak valid
     * @return float stok baru
     */
    public static function move(int $id, string $tipe, float $qty, string $tanggal, string $keterangan, ?int $orderId): float
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $new = self::applyMove($id, $tipe, $qty, $tanggal, $keterangan, $orderId);
            $pdo->commit();
            return $new;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Koreksi stok ke angka hasil hitung fisik; selisihnya dicatat sebagai mutasi. */
    public static function adjust(int $id, float $actual, string $tanggal, string $keterangan): float
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $row = Database::one('SELECT stok FROM materials WHERE id = ? FOR UPDATE', [$id]) ?? throw new DomainException('Bahan tidak ditemukan.');
            $diff = round($actual - (float) $row['stok'], 2);
            if ($diff == 0.0) {
                throw new DomainException('Stok hasil hitung sama dengan stok tercatat.');
            }
            $label = 'Penyesuaian stok' . ($keterangan !== '' ? ': ' . $keterangan : '');
            $new = self::applyMove($id, $diff > 0 ? 'masuk' : 'keluar', abs($diff), $tanggal, $label, null);
            $pdo->commit();
            return $new;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Harus dipanggil di dalam transaksi. */
    private static function applyMove(int $id, string $tipe, float $qty, string $tanggal, string $keterangan, ?int $orderId): float
    {
        $qty = round($qty, 2);
        if (!in_array($tipe, ['masuk', 'keluar'], true) || $qty <= 0 || $qty > 1000000) {
            throw new DomainException('Jumlah harus lebih dari 0 dan maksimal 1.000.000.');
        }
        $m = Database::one('SELECT * FROM materials WHERE id = ? FOR UPDATE', [$id]) ?? throw new DomainException('Bahan tidak ditemukan.');
        $before = (float) $m['stok'];
        $after = round($tipe === 'masuk' ? $before + $qty : $before - $qty, 2);
        if ($after < 0) {
            throw new DomainException('Stok tidak cukup (tersedia ' . self::fmt($before) . ' ' . $m['satuan'] . ').');
        }

        Database::update('materials', ['stok' => $after], 'id = ?', [$id]);
        Database::insert('material_movements', [
            'material_id' => $id,
            'tipe'        => $tipe,
            'qty'         => $qty,
            'order_id'    => $orderId,
            'keterangan'  => mb_substr($keterangan, 0, 200),
            'tanggal'     => $tanggal,
        ]);

        // Beri tahu admin saat stok baru melewati batas minimum (sekali, bukan tiap mutasi).
        if ($before > (float) $m['stok_minimum'] && $after <= (float) $m['stok_minimum']) {
            notify_admins('Stok ' . $m['nama'] . ' menipis: ' . self::fmt($after) . ' ' . $m['satuan'] . ' (minimum ' . self::fmt((float) $m['stok_minimum']) . ')');
        }
        return $after;
    }

    /** @return array{items: array, total: int, page: int, pages: int} */
    public static function movements(int $id, int $page, int $perPage = 20): array
    {
        $total = (int) Database::value('SELECT COUNT(*) FROM material_movements WHERE material_id = ?', [$id]);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $items = Database::all(
            'SELECT mm.*, o.kode_order FROM material_movements mm LEFT JOIN orders o ON o.id = mm.order_id
             WHERE mm.material_id = ? ORDER BY mm.tanggal DESC, mm.id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage),
            [$id]
        );
        return compact('items', 'total', 'page', 'pages');
    }

    /** Pemakaian bahan untuk satu pesanan. */
    public static function usageForOrder(int $orderId): array
    {
        return Database::all(
            'SELECT mm.*, m.nama, m.satuan FROM material_movements mm JOIN materials m ON m.id = mm.material_id
             WHERE mm.order_id = ? ORDER BY mm.id',
            [$orderId]
        );
    }

    /** Format angka stok: 12,5 atau 12 (tanpa nol di belakang). */
    public static function fmt(float $n): string
    {
        $s = number_format($n, 2, ',', '.');
        return rtrim(rtrim($s, '0'), ',');
    }
}
