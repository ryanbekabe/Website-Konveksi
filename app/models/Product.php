<?php

class Product
{
    public const PER_PAGE = 12;

    /**
     * Daftar produk dengan filter & pagination.
     * $filters: q, category (slug), only_active (bool)
     * @return array{items: array, total: int, page: int, pages: int}
     */
    public static function paginate(array $filters = [], int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['only_active'])) {
            $where[] = 'p.aktif = 1';
        }
        if (!empty($filters['q'])) {
            $where[] = '(p.nama LIKE ? OR p.bahan LIKE ?)';
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            array_push($params, $like, $like);
        }
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category'];
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::value(
            "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql",
            $params
        );
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        // LIMIT/OFFSET adalah integer hasil cast, aman disisipkan langsung.
        $items = Database::all(
            "SELECT p.*, c.nama AS kategori,
                    (SELECT file FROM product_images i WHERE i.product_id = p.id ORDER BY i.urutan, i.id LIMIT 1) AS gambar
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE $whereSql ORDER BY p.id DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );

        return compact('items', 'total', 'page', 'pages');
    }

    public static function latest(int $limit = 4): array
    {
        return Database::all(
            'SELECT p.*, c.nama AS kategori,
                    (SELECT file FROM product_images i WHERE i.product_id = p.id ORDER BY i.urutan, i.id LIMIT 1) AS gambar
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.aktif = 1 ORDER BY p.id DESC LIMIT ' . (int) $limit
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT p.*, (SELECT file FROM product_images i WHERE i.product_id = p.id ORDER BY i.urutan, i.id LIMIT 1) AS gambar
             FROM products p WHERE p.id = ?',
            [$id]
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one(
            'SELECT p.*, c.nama AS kategori,
                    (SELECT file FROM product_images i WHERE i.product_id = p.id ORDER BY i.urutan, i.id LIMIT 1) AS gambar
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.aktif = 1',
            [$slug]
        );
    }

    public static function priceTiers(int $productId): array
    {
        return Database::all('SELECT * FROM price_tiers WHERE product_id = ? ORDER BY min_qty', [$productId]);
    }

    public static function variants(int $productId): array
    {
        return Database::all('SELECT * FROM product_variants WHERE product_id = ? ORDER BY id', [$productId]);
    }

    /** Harga satuan berdasarkan jumlah (harga bertingkat), default harga dasar. */
    public static function unitPrice(array $product, int $qty): float
    {
        $price = (float) $product['harga_dasar'];
        foreach (self::priceTiers((int) $product['id']) as $tier) {
            $max = $tier['max_qty'] === null ? PHP_INT_MAX : (int) $tier['max_qty'];
            if ($qty >= (int) $tier['min_qty'] && $qty <= $max) {
                $price = (float) $tier['harga'];
            }
        }
        return $price;
    }

    /** Slug unik: tambahkan -2, -3, ... bila sudah dipakai. */
    public static function uniqueSlug(string $nama, ?int $ignoreId = null): string
    {
        $base = slugify($nama);
        $slug = $base;
        $i = 2;
        while (Database::value('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $ignoreId ?? 0])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public static function create(array $data, ?string $image): int
    {
        $id = Database::insert('products', $data);
        if ($image) {
            Database::insert('product_images', ['product_id' => $id, 'file' => $image, 'urutan' => 0]);
        }
        return $id;
    }

    public static function update(int $id, array $data, ?string $newImage): void
    {
        Database::update('products', $data, 'id = ?', [$id]);
        if ($newImage) {
            $old = Database::value('SELECT file FROM product_images WHERE product_id = ? ORDER BY urutan, id LIMIT 1', [$id]);
            if ($old) {
                Database::update('product_images', ['file' => $newImage], 'product_id = ? AND file = ?', [$id, $old]);
                Upload::remove('products', $old);
            } else {
                Database::insert('product_images', ['product_id' => $id, 'file' => $newImage, 'urutan' => 0]);
            }
        }
    }

    public static function delete(int $id): void
    {
        $files = Database::all('SELECT file FROM product_images WHERE product_id = ?', [$id]);
        Database::delete('products', 'id = ?', [$id]);
        foreach ($files as $f) {
            Upload::remove('products', $f['file']);
        }
    }
}
