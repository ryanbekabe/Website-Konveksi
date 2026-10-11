<?php

class Order
{
    public const PER_PAGE = 20;
    public const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];

    public const STATUS = [
        'menunggu_pembayaran'  => 'Menunggu pembayaran DP',
        'dp_dibayar'           => 'DP dibayar',
        'desain_mockup'        => 'Pembuatan mockup',
        'menunggu_persetujuan' => 'Menunggu persetujuan mockup',
        'produksi'             => 'Dalam produksi',
        'qc'                   => 'Quality control',
        'menunggu_pelunasan'   => 'Menunggu pelunasan',
        'siap_kirim'           => 'Siap dikirim',
        'dikirim'              => 'Dikirim',
        'selesai'              => 'Selesai',
        'dibatalkan'           => 'Dibatalkan',
    ];

    /** Urutan tahapan untuk garis waktu (tanpa dibatalkan). */
    public const FLOW = [
        'menunggu_pembayaran', 'dp_dibayar', 'desain_mockup', 'menunggu_persetujuan',
        'produksi', 'qc', 'menunggu_pelunasan', 'siap_kirim', 'dikirim', 'selesai',
    ];

    /** Perpindahan status manual yang diizinkan bagi staf. Sebagian lain terjadi otomatis. */
    private const NEXT = [
        'menunggu_pembayaran'  => ['dibatalkan'],
        'dp_dibayar'           => ['desain_mockup', 'produksi', 'dibatalkan'],
        'desain_mockup'        => ['produksi', 'dibatalkan'],
        'menunggu_persetujuan' => ['desain_mockup', 'dibatalkan'],
        'produksi'             => ['qc'],
        'qc'                   => ['produksi', 'menunggu_pelunasan'],
        'menunggu_pelunasan'   => ['siap_kirim'],
        'siap_kirim'           => ['dikirim'],
        'dikirim'              => ['selesai'],
    ];

    public const STAGES = ['pemotongan', 'penjahitan', 'sablon_bordir', 'finishing', 'qc', 'packing'];

    public const STAGE_LABELS = [
        'pemotongan'    => 'Pemotongan',
        'penjahitan'    => 'Penjahitan',
        'sablon_bordir' => 'Sablon / Bordir',
        'finishing'     => 'Finishing',
        'qc'            => 'Quality Control',
        'packing'       => 'Packing',
    ];

    public static function stageLabel(string $tahap): string
    {
        return self::STAGE_LABELS[$tahap] ?? ucfirst($tahap);
    }

    public static function label(string $status): string
    {
        return self::STATUS[$status] ?? $status;
    }

    public static function allowedNext(string $status): array
    {
        return self::NEXT[$status] ?? [];
    }

    /* ---------- Query ---------- */

    public static function findByCode(string $kode): ?array
    {
        return Database::one(
            'SELECT o.*, u.nama AS pelanggan, u.email, u.no_hp AS pelanggan_hp
             FROM orders o JOIN users u ON u.id = o.user_id WHERE o.kode_order = ?',
            [$kode]
        );
    }

    public static function forUser(int $userId): array
    {
        return Database::all('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC', [$userId]);
    }

    public static function paginate(string $status, string $q, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        if ($status !== '' && isset(self::STATUS[$status])) {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(o.kode_order LIKE ? OR u.nama LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            array_push($params, $like, $like);
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id WHERE $whereSql", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $items = Database::all(
            "SELECT o.*, u.nama AS pelanggan FROM orders o JOIN users u ON u.id = o.user_id
             WHERE $whereSql ORDER BY o.id DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (int) $offset,
            $params
        );
        return compact('items', 'total', 'page', 'pages');
    }

    public static function items(int $orderId): array
    {
        $rows = Database::all(
            'SELECT i.*, p.nama, p.slug FROM order_items i JOIN products p ON p.id = i.product_id WHERE i.order_id = ? ORDER BY i.id',
            [$orderId]
        );
        foreach ($rows as &$r) {
            $r['spec'] = json_decode((string) $r['spesifikasi'], true) ?: [];
        }
        return $rows;
    }

    public static function designs(int $orderId, ?string $tipe = null): array
    {
        $sql = 'SELECT * FROM order_designs WHERE order_id = ?' . ($tipe ? ' AND tipe = ?' : '') . ' ORDER BY id';
        return Database::all($sql, $tipe ? [$orderId, $tipe] : [$orderId]);
    }

    public static function payments(int $orderId): array
    {
        return Database::all('SELECT * FROM payments WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    public static function shipment(int $orderId): ?array
    {
        return Database::one('SELECT * FROM shipments WHERE order_id = ?', [$orderId]);
    }

    public static function paidTotal(int $orderId): float
    {
        return (float) Database::value("SELECT COALESCE(SUM(jumlah), 0) FROM payments WHERE order_id = ? AND status = 'terverifikasi'", [$orderId]);
    }

    /**
     * Pembayaran berikutnya yang harus dilakukan pelanggan, atau null bila tidak ada.
     * @return array{jenis: string, jumlah: float}|null
     */
    public static function nextPayment(array $order): ?array
    {
        if (in_array($order['status'], ['dibatalkan', 'selesai'], true)) {
            return null;
        }
        if ($order['status'] === 'menunggu_pembayaran') {
            return ['jenis' => 'dp', 'jumlah' => (float) $order['dp_jumlah']];
        }
        if ($order['status'] === 'menunggu_pelunasan') {
            $sisa = (float) $order['total'] - self::paidTotal((int) $order['id']);
            return $sisa > 0 ? ['jenis' => 'pelunasan', 'jumlah' => $sisa] : null;
        }
        return null;
    }

    /* ---------- Pembuatan pesanan ---------- */

    /** Validasi alamat kirim dari input form. @return array{0: array, 1: array} [data, errors] */
    public static function validateShipping(array $in): array
    {
        $v = new Validator($in, [
            'penerima' => 'required|max:100',
            'no_hp'    => 'required|phone',
            'alamat'   => 'required|max:500',
            'kota'     => 'required|max:100',
            'provinsi' => 'required|max:100',
            'kode_pos' => 'max:10',
        ], ['penerima' => 'Nama penerima', 'no_hp' => 'No. HP', 'alamat' => 'Alamat', 'kota' => 'Kota', 'provinsi' => 'Provinsi', 'kode_pos' => 'Kode pos']);

        $data = [];
        foreach (['penerima', 'no_hp', 'alamat', 'kota', 'provinsi', 'kode_pos'] as $k) {
            $data[$k] = trim((string) ($in[$k] ?? ''));
        }
        return [$data, $v->errors()];
    }

    /**
     * Buat pesanan dari daftar baris.
     * $lines: [['product_id' => int, 'ukuran' => string, 'warna' => string, 'qty' => int], ...]
     * $ship : penerima, no_hp, alamat, kota, provinsi, kode_pos
     * Harga dihitung ulang di server (harga bertingkat berdasarkan total qty per produk).
     * @throws InvalidArgumentException bila data tidak valid
     */
    public static function place(int $userId, array $lines, array $ship, string $catatan, ?string $designFile): array
    {
        if (!$lines) {
            throw new InvalidArgumentException('Keranjang masih kosong.');
        }

        // Total qty per produk -> harga satuan (bertingkat) dan validasi minimum order.
        $qtyByProduct = [];
        foreach ($lines as $l) {
            $qtyByProduct[$l['product_id']] = ($qtyByProduct[$l['product_id']] ?? 0) + (int) $l['qty'];
        }
        $products = [];
        $unit = [];
        $minCustom = (int) config('app.min_qty_custom', 12);
        foreach ($qtyByProduct as $pid => $qty) {
            $product = Product::find((int) $pid);
            if (!$product || !$product['aktif']) {
                throw new InvalidArgumentException('Ada produk yang tidak tersedia lagi.');
            }
            if ($product['tipe'] === 'custom' && $qty < $minCustom) {
                throw new InvalidArgumentException("Pesanan custom \"{$product['nama']}\" minimal $minCustom pcs.");
            }
            $products[$pid] = $product;
            $unit[$pid] = Product::unitPrice($product, $qty);
        }

        $hasCustom = $designFile !== null;
        $subtotal = 0.0;
        foreach ($lines as $l) {
            $subtotal += $unit[$l['product_id']] * (int) $l['qty'];
            $hasCustom = $hasCustom || $products[$l['product_id']]['tipe'] === 'custom';
        }
        $total = $subtotal;
        $dpPersen = min(100, max(0, (float) Setting::get('dp_persen', 50)));
        $dp = round($total * $dpPersen / 100, 0);

        $alamat = sprintf(
            "%s (%s)\n%s\n%s, %s %s",
            $ship['penerima'], $ship['no_hp'], $ship['alamat'], $ship['kota'], $ship['provinsi'], $ship['kode_pos']
        );

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $orderId = 0;
            $kode = '';
            for ($try = 0; $try < 5; $try++) {
                $kode = 'KNV-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                try {
                    $orderId = Database::insert('orders', [
                        'kode_order'   => $kode,
                        'user_id'      => $userId,
                        'tipe'         => $hasCustom ? 'custom' : 'ready',
                        'status'       => 'menunggu_pembayaran',
                        'subtotal'     => $subtotal,
                        'total'        => $total,
                        'dp_jumlah'    => $dp,
                        'catatan'      => $catatan,
                        'alamat_kirim' => trim($alamat),
                    ]);
                    break;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000') { // selain duplikat kode, lempar ulang
                        throw $e;
                    }
                }
            }
            if (!$orderId) {
                throw new RuntimeException('Gagal membuat kode pesanan.');
            }

            foreach ($lines as $l) {
                Database::insert('order_items', [
                    'order_id'     => $orderId,
                    'product_id'   => $l['product_id'],
                    'qty'          => (int) $l['qty'],
                    'harga_satuan' => $unit[$l['product_id']],
                    'spesifikasi'  => json_encode(['ukuran' => $l['ukuran'], 'warna' => $l['warna']], JSON_UNESCAPED_UNICODE),
                ]);
            }
            if ($designFile !== null) {
                Database::insert('order_designs', ['order_id' => $orderId, 'file' => $designFile, 'tipe' => 'desain']);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        notify_admins("Pesanan baru $kode dari " . (Auth::user()['nama'] ?? 'pelanggan'));
        return self::findByCode($kode);
    }

    /* ---------- Perubahan status ---------- */

    /** Ubah status; saat masuk 'produksi' tahapan produksi dibuat bila belum ada. */
    public static function setStatus(int $orderId, string $status): void
    {
        Database::update('orders', ['status' => $status], 'id = ?', [$orderId]);

        if ($status === 'produksi'
            && !Database::value('SELECT COUNT(*) FROM production_stages WHERE order_id = ?', [$orderId])) {
            foreach (self::STAGES as $stage) {
                Database::insert('production_stages', ['order_id' => $orderId, 'tahap' => $stage]);
            }
        }
    }
}
