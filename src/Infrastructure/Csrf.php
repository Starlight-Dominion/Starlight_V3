<?php

declare(strict_types=1);

namespace sdo\Infrastructure;

class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;
        return $token;
    }

    public static function getToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            return self::generateToken();
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function verifyToken(string $token): bool
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function regenerate(): void
    {
        self::generateToken();
    }

    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
