<?php

class User
{
    public const ROLES = [
        'admin'     => 'Admin',
        'produksi'  => 'Produksi',
        'keuangan'  => 'Keuangan',
        'pelanggan' => 'Pelanggan',
    ];
    public const PER_PAGE = 20;

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    public static function emailTaken(string $email, int $ignoreId = 0): bool
    {
        return (bool) Database::value('SELECT id FROM users WHERE email = ? AND id <> ?', [strtolower($email), $ignoreId]);
    }

    public static function create(string $nama, string $email, string $noHp, string $password, string $role = 'pelanggan'): int
    {
        return Database::insert('users', [
            'nama'          => $nama,
            'email'         => strtolower($email),
            'no_hp'         => $noHp,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $role,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }
        Database::update('users', $data, 'id = ?', [$id]);
    }

    public static function setPassword(int $id, string $password): void
    {
        Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$id]);
    }

    /** Daftar pengguna untuk admin. @return array{items: array, total: int, page: int, pages: int} */
    public static function paginate(string $q, string $role, string $status, int $page): array
    {
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.nama LIKE ? OR u.email LIKE ? OR u.no_hp LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            array_push($params, $like, $like, $like);
        }
        if (isset(self::ROLES[$role])) {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }
        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) FROM users u WHERE $whereSql", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $items = Database::all(
            "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS jumlah_pesanan
             FROM users u WHERE $whereSql ORDER BY u.id DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (int) $offset,
            $params
        );
        return compact('items', 'total', 'page', 'pages');
    }

    /** Apakah akun admin masih memakai kata sandi bawaan dari seed.sql. */
    public static function usesDefaultPassword(array $user): bool
    {
        return $user['role'] === 'admin' && password_verify('admin123', $user['password_hash']);
    }
}
