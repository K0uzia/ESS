<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        $content = self::capture($template, $data);
        echo $layout === null ? $content : self::capture($layout, $data + ['content' => $content]);
    }

    /** @param array<string, mixed> $data */
    private static function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require ROOT . '/templates/' . $template . '.php';
        return (string) ob_get_clean();
    }
}
