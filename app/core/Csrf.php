<?php
class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function input(): string
    {
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' .
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function check(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION[self::SESSION_KEY])
            && is_string($_SESSION[self::SESSION_KEY])
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function requireValid(?string $token = null): void
    {
        $token ??= $_POST[self::FIELD_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!self::check($token)) {
            throw new RuntimeException("Session expiree. Rechargez la page puis reessayez.");
        }
    }
}
