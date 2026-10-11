<?php

class Setting
{
    private static ?array $cache = null;

    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            self::$cache = array_column(Database::all('SELECT `key`, `value` FROM settings'), 'value', 'key');
        }
        return self::$cache[$key] ?? $default;
    }
}
