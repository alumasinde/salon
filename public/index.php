<?php

/** Single HTTP entry point. Everything the browser sees lives under public/. */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/routes.php';

use Salon\Http\Request;
use Salon\Model\Setting;

$request = new Request();

if ($request->isSecure()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

try {
    Setting::all();
} catch (Throwable $t) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Setup needed</title>'
        . '<body style="font:16px system-ui;max-width:640px;margin:60px auto;padding:0 20px">'
        . '<h1>The site needs its database</h1><p>Run these two commands from the project folder:</p>'
        . '<pre style="background:#f4f4f4;padding:14px;border-radius:8px;overflow:auto">'
        . 'php bin/migrate.php up --create-database' . "\n" . 'php bin/admin.php create</pre>'
        . (Salon\Support\Config::debug() ? '<p style="color:#b3261e">' . e($t->getMessage()) . '</p>' : '')
        . '</body>';
    exit;
}

echo salon_routes()->dispatch($request);
