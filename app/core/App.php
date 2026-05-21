<?php
/**
 * core/App.php
 * Routeur frontal — parse l'URL et dispatch vers le bon controller/méthode
 */
class App
{
    private array $routes = [];

    public function __construct()
    {
        require ROOT . '/routes/web.php';
    }

    /**
     * Enregistre une route GET/POST
     * Pattern: 'controller/method' ou 'controller/method/{param}'
     */
    public function route(string $pattern, callable $handler): void
    {
        $this->routes[$pattern] = $handler;
    }

    public function run(): void
    {
        $url    = trim($_GET['url'] ?? 'dashboard', '/');
        $parts  = explode('/', $url);

        $segment0 = $parts[0] ?? '';
        $segment1 = $parts[1] ?? '';
        $segment2 = $parts[2] ?? null;

        // Routes définies dans web.php
        foreach ($this->routes as $pattern => $handler) {
            if ($this->match($pattern, $url, $params)) {
                call_user_func_array($handler, $params);
                return;
            }
        }

        // Résolution automatique : controller/action/param
        // On essaie d'abord la forme exacte, puis le singulier
        // (ex. "offres" → OffresController, sinon OffreController)
        $action = $segment1 ?: 'index';
        $param  = $segment2;

        $candidates = [ucfirst($segment0) . 'Controller'];
        if (str_ends_with($segment0, 's')) {
            $candidates[] = ucfirst(rtrim($segment0, 's')) . 'Controller';
        }

        foreach ($candidates as $controllerName) {
            if (class_exists($controllerName)) {
                $controller = new $controllerName();
                if (method_exists($controller, $action)) {
                    $controller->$action($param);
                    return;
                }
                break;
            }
        }

        // 404
        http_response_code(404);
        require ROOT . '/app/views/errors/404.php';
    }

    private function match(string $pattern, string $url, ?array &$params): bool
    {
        $params = [];
        // Convertit {param} en groupe de capture
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (preg_match($regex, $url, $matches)) {
            array_shift($matches);
            $params = $matches;
            return true;
        }
        return false;
    }
}
