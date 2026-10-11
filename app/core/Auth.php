<?php

class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public static function check(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function user(): ?array
    {
        static $cache = null;
        if ($cache === null && self::check()) {
            $cache = User::find((int) Session::get('user_id'));
            if (!$cache || $cache['status'] !== 'aktif') {
                self::logout();
                $cache = null;
            }
        }
        return $cache;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) Session::get('user_id') : null;
    }

    public static function hasRole(array $roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    /** Batasi percobaan login gagal per IP/email. */
    public static function tooManyAttempts(string $email): bool
    {
        $count = Database::value(
            'SELECT COUNT(*) FROM login_attempts
             WHERE (ip = ? OR email = ?) AND created_at > (NOW() - INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)',
            [client_ip(), $email]
        );
        return (int) $count >= self::MAX_ATTEMPTS;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if ($user && $user['status'] === 'aktif' && password_verify($password, $user['password_hash'])) {
            Database::delete('login_attempts', 'ip = ? OR email = ?', [client_ip(), $email]);
            Session::regenerate();
            Session::set('user_id', (int) $user['id']);
            return true;
        }

        Database::insert('login_attempts', ['ip' => client_ip(), 'email' => $email]);
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
