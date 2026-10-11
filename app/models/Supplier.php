<?php

class Supplier
{
    public static function all(): array
    {
        return Database::all(
            'SELECT s.*, (SELECT COUNT(*) FROM materials m WHERE m.supplier_id = s.id) AS jumlah_bahan
             FROM suppliers s ORDER BY s.nama'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM suppliers WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insert('suppliers', $data);
    }

    public static function update(int $id, array $data): void
    {
        Database::update('suppliers', $data, 'id = ?', [$id]);
    }

    /** Bahan yang memakai supplier ini akan menjadi "tanpa supplier" (FK ON DELETE SET NULL). */
    public static function delete(int $id): void
    {
        Database::delete('suppliers', 'id = ?', [$id]);
    }
}
