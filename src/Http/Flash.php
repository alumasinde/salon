<?php

declare(strict_types=1);

namespace Salon\Http;

/** One-shot messages between requests. */
final class Flash
{
    public static function push(string $message, string $type = 'ok'): void
    {
        Auth::start();
        $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
    }

    public static function take(): array
    {
        Auth::start();
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
