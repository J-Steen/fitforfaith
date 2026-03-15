<?php
namespace App\Core;

class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Register a GET route.
     */
    public function get(string $pattern, array|string $handler, array $middleware = []): void {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }

    /**
     * Register a POST route.
     */
    public function post(string $pattern, array|string $handler, array $middleware = []): void {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    private function addRoute(string $method, string $pattern, array|string $handler, array $middleware): void {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Dispatch the current request to the matching route.
     */
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = '/' . trim(str_replace($this->basePath, '', $uri), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                continue;
            }

            $params = $this->match($route['pattern'], $uri);
            if ($params === null) continue;

            // Run middleware
            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw);
            }

            // CSRF check for all POSTs except those with 'no-csrf' middleware
            if ($method === 'POST' && !in_array('no-csrf', $route['middleware'])) {
                CSRF::verifyRequest();
            }

            // Dispatch to handler
            $this->callHandler($route['handler'], $params);
            return;
        }

        // No route matched
        http_response_code(404);
        if (file_exists(VIEW_PATH . 'errors/404.php')) {
            include VIEW_PATH . 'errors/404.php';
        } else {
            echo '<h1>404 Not Found</h1>';
        }
    }

    /**
     * Match URI against pattern. Returns params array or null.
     */
    private function match(string $pattern, string $uri): ?array {
        // Convert :param to named capture groups
        $regex = preg_replace('/\/:([a-zA-Z_]+)/', '/(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) return null;

        // Return only named params
        $params = [];
        foreach ($matches as $key => $val) {
            if (is_string($key)) $params[$key] = $val;
        }
        return $params;
    }

    private function runMiddleware(string $mw): void {
        match($mw) {
            'auth'     => Auth::require(),
            'admin'    => Auth::requireAdmin(),
            'no-csrf'  => null, // handled in dispatch
            default    => null,
        };
    }

    private function callHandler(array|string $handler, array $params): void {
        if (is_string($handler)) {
            [$class, $method] = explode('@', $handler);
        } else {
            [$class, $method] = $handler;
        }

        // Support App\Controllers\ prefix
        if (!str_contains($class, '\\')) {
            $class = 'App\\Controllers\\' . $class;
        }

        $controller = new $class();
        $controller->$method($params);
    }
}
