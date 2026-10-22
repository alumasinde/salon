<?php

declare(strict_types=1);

namespace Salon\Http;

/** Read-only view of the incoming request. */
final class Request
{
    public readonly string $method;
    public readonly string $path;
    public readonly string $ip;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = self::resolvePath();
        $this->ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private static function resolvePath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        // Fallback for hosts without rewrite: /salon/public/index.php/admin/bookings
        if (str_starts_with($uri, '/public/index.php')) {
            $uri = substr($uri, strlen('/public/index.php'));
        }
        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? mb_substr($value, 0, 200) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->query($key, (string) $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function isSecure(): bool
    {
        return !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /** Absolute URL for the current site section, used by the WhatsApp/map links. */
    public function selfUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path() . '/';
    }
}
