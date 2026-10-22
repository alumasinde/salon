<?php

/**
 * Router for PHP's built-in web server, used only while developing:
 *
 *   php -S 127.0.0.1:8080 -t public bin/serve.php
 *
 * Apache and nginx do not need this — their .htaccess / try_files rules already
 * send every request to public/index.php.
 */

declare(strict_types=1);

$publicDir = dirname(__DIR__) . '/public';
$path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$static = realpath($publicDir . $path);

if ($static !== false && is_file($static) && !str_ends_with(strtolower($static), '.php')) {
    return false; // let the built-in server stream the asset
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicDir . '/index.php';

require $publicDir . '/index.php';
