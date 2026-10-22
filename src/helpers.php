<?php

declare(strict_types=1);

use Salon\Support\Config;

/** Escape for HTML output. Every dynamic value in a template goes through this. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Path under which the app is served, e.g. "" or "/salon". */
function base_path(): string
{
    static $base;
    if ($base !== null) {
        return $base;
    }
    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $file = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));

    // Where the front controller sits relative to the document root, e.g. "/salon/public/index.php".
    $dir = $docRoot !== '' && str_starts_with($file, $docRoot)
        ? substr($file, strlen($docRoot))
        : rtrim(dirname($script), '/');

    // Browsers never see the front controller name or the public/ folder:
    // /salon/public/index.php is reached as /salon.
    $dir = (string) preg_replace('#/index\.php$#i', '', $dir);
    $dir = (string) preg_replace('#/public$#i', '', $dir);
    return $base = rtrim($dir, '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)), true, 303);
    exit;
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}
