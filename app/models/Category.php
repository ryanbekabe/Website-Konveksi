<?php

class Category
{
    public static function all(): array
    {
        return Database::all('SELECT * FROM categories ORDER BY nama');
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }
}
