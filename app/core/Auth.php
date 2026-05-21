<?php
/**
 * core/Auth.php
 * Gestion des sessions et des rôles
 */
class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): int
    {
        return (int)($_SESSION['id_user'] ?? 0);
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Vérifie si l'utilisateur a l'un des rôles donnés.
     * @param string|array $roles
     */
    public static function hasRole(string|array $roles): bool
    {
        if (!self::check()) return false;
        $roles = (array) $roles;
        return in_array(self::role(), $roles, true);
    }

    /**
     * Redirige vers login si non connecté.
     * Si $roles est fourni, vérifie aussi le rôle.
     */
    public static function require(string|array $roles = []): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/index.php?url=auth/login');
            exit;
        }
        if (!empty($roles) && !self::hasRole($roles)) {
            http_response_code(403);
            die('<div style="font-family:monospace;background:#0d1520;color:#f43f5e;padding:2rem;text-align:center">
                <h2>🚫 403 — Accès refusé</h2>
                <p>Vous n\'avez pas les droits pour accéder à cette page.</p>
                <a href="' . BASE_URL . '/index.php?url=dashboard" style="color:#10b981">← Retour au dashboard</a>
            </div>');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
