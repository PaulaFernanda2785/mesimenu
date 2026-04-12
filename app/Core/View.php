<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'layouts/app'): string
    {
        $templatePath = BASE_PATH . '/resources/views/' . $template . '.php';
        $layoutPath = BASE_PATH . '/resources/views/' . $layout . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('View não encontrada: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        if (!file_exists($layoutPath)) {
            return $content;
        }

        ob_start();
        require $layoutPath;
        return ob_get_clean();
    }
}
