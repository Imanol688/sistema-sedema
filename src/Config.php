<?php
declare(strict_types=1);

namespace Sedema;

final class Config
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }

    public static function appUrl(): string
    {
        return rtrim((string) self::get('APP_URL', 'http://localhost/sedema-auth/public'), '/');
    }
}

