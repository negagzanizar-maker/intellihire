<?php
/**
 * public/index.php
 * Point d'entrée unique — IntelliHire
 */

// ── Constantes ────────────────────────────────────────────────
define('ROOT',     dirname(__DIR__));
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
define('BASE_URL', $scheme . '://' . $host . (($basePath === '' || $basePath === '/') ? '' : $basePath));

// ── Erreurs ─────────────────
// Production-friendly by default. Set SMARTRECRUIT_DEBUG=1 locally to display errors.
$debug = filter_var(getenv('SMARTRECRUIT_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── Session ───────────────────────────────────────────────────
session_start();

// ── Autoloader ────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $dirs = [
        ROOT . '/config/',
        ROOT . '/app/core/',
        ROOT . '/app/models/',
        ROOT . '/app/services/',
        ROOT . '/app/controllers/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Initialisation DB ─────────────────────────────────────────
Database::getInstance();

// ── Routeur ───────────────────────────────────────────────────
$app = new App();
$app->run();

