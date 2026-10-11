<?php

class Router
{
    private array $routes = [];

    public function get(string $pattern, string $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, string $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, string $handler, array $middleware): void
    {
        // /produk/{slug} -> #^/produk/(?P<slug>[^/]+)$#
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);

        if ($method === 'POST' && !Csrf::validate($_POST['_token'] ?? null)) {
            abort(419, 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw);
            }

            [$class, $action] = explode('@', $route['handler']);
            (new $class())->$action(...array_values($params));
            return;
        }

        abort(404);
    }

    private function runMiddleware(string $mw): void
    {
        switch ($mw) {
            case 'auth':
                if (!Auth::check()) {
                    Session::set('intended', $_SERVER['REQUEST_URI'] ?? '/');
                    flash('error', 'Silakan masuk terlebih dahulu.');
                    redirect('/masuk');
                }
                break;
            case 'admin':
                if (!Auth::check()) {
                    redirect('/masuk');
                }
                if (!Auth::hasRole(['admin', 'produksi', 'keuangan'])) {
                    abort(403, 'Anda tidak memiliki akses ke halaman ini.');
                }
                break;
            case 'guest':
                if (Auth::check()) {
                    redirect('/');
                }
                break;
        }
    }
}
