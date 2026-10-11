<?php

class User
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
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
}
