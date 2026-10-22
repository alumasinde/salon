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
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $file = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $base = '';
    if ($docRoot !== '' && str_starts_with($file, $docRoot)) {
        $base = rtrim(substr($script, 0, strlen($file) - strlen($docRoot)), '/');
    }
    if ($base === '') {
        $base = rtrim(dirname($script), '/');
    }
    return $base;
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
