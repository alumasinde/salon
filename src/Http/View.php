<?php

declare(strict_types=1);

namespace Salon\Http;

/** Template renderer: plain PHP views inside a layout. */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::render($layout, $data + ['content' => $content]);
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        $file = SALON_ROOT . '/src/views/' . str_replace('.', '/', $template) . '.php';
        if (!is_readable($file)) {
            throw new \RuntimeException('Missing view: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
