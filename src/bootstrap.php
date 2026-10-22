<?php

/**
 * Application bootstrap: autoload, configuration, error handling, security headers.
 * Every HTTP entry point requires this file first.
 */

declare(strict_types=1);

define('SALON_ROOT', dirname(__DIR__));

$autoload = SALON_ROOT . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    exit('Dependencies are missing. Run "composer install" in the project folder, then reload.');
}
require $autoload;

require SALON_ROOT . '/src/helpers.php';

Dotenv\Dotenv::createImmutable(SALON_ROOT)->safeLoad();

use Salon\Support\Config;

date_default_timezone_set(Config::get('APP_TIMEZONE', 'Africa/Nairobi'));

error_reporting(E_ALL);
ini_set('display_errors', Config::debug() ? '1' : '0');
ini_set('log_errors', '1');
ini_set('expose_php', '0');

$logFile = SALON_ROOT . '/storage/logs/app.log';
@mkdir(dirname($logFile), 0775, true);
ini_set('error_log', $logFile);

set_exception_handler(static function (Throwable $t) use ($logFile): void {
    error_log($t::class . ': ' . $t->getMessage()
        . ' in ' . $t->getFile() . ':' . $t->getLine());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $t->getMessage() . PHP_EOL);
        if (Config::debug()) {
            fwrite(STDERR, $t->getTraceAsString() . PHP_EOL);
        }
        exit(1);
    }
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    if (Config::debug()) {
        echo '<h1>Server error</h1><pre>' . e($t->getMessage()) . "\n" . e($t->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Something went wrong</h1><p>Please try again or call the salon.</p>';
    }
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Nonce for inline scripts, exposed to templates through the CSP header.
define('SALON_NONCE', bin2hex(random_bytes(16)));

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    $isHttps = !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'nonce-' . SALON_NONCE . '\'; '
        . "style-src 'self' 'nonce-" . SALON_NONCE . "' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; "
        . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header_remove('X-Powered-By');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
