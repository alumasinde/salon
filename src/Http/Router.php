<?php

declare(strict_types=1);

namespace Salon\Http;

/** Small path router. Patterns may end with a captured segment: /admin/services/{id}. */
final class Router
{
    private array $routes = [];
    private $notFound = null;

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [strtoupper($method), $pattern, $handler];
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): void
    {
        $this->add('ANY', $pattern, $handler);
    }

    public function notFound(callable $handler): void
    {
        $this->notFound = $handler;
    }

    public function dispatch(Request $request): mixed
    {
        $pathMatched = false;
        foreach ($this->routes as [$method, $pattern, $handler]) {
            $params = self::match($pattern, $request->path);
            if ($params === null) {
                continue;
            }
            $pathMatched = true;
            if ($method === 'ANY' || $method === $request->method) {
                return $handler($request, ...array_values($params));
            }
        }
        if ($pathMatched) {
            http_response_code(405);
            header('Allow: GET, POST');
            return ($this->notFound)($request, 'That action is not allowed here.');
        }
        http_response_code(404);
        return ($this->notFound)($request, 'Page not found.');
    }

    /** @return array<string,string>|null */
    private static function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace_callback('/\{([a-z_]+)\}/i',
            static fn(array $m): string => '(?P<' . $m[1] . '>[A-Za-z0-9_.\-]{1,64})', $pattern);
        $regex = '#^' . rtrim($regex, '/') . '/?$#i';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
