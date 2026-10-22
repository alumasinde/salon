<?php

declare(strict_types=1);

namespace Salon\Support;

/** Minimal .env reader. Values are only used for infrastructure config. */
final class Env
{
    public static function load(string $file): void
    {
        if (!is_readable($file)) {
            return;
        }
        foreach (preg_split('/\R+/', file_get_contents($file)) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim(preg_replace('/^export\s+/', '', $key));
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                continue;
            }
            $value = trim($value);
            if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && $value[0] === substr($value, -1)) {
                $value = substr($value, 1, -1);
            } elseif (str_contains($value, '#')) {
                $value = trim(substr($value, 0, strpos($value, '#')));
            }
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}
