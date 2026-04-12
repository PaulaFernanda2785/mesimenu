<?php
declare(strict_types=1);

if (!function_exists('config')) {
    function config(string $file): array
    {
        $path = BASE_PATH . '/config/' . $file . '.php';
        if (!file_exists($path)) {
            throw new RuntimeException('Arquivo de configuração não encontrado: ' . $file);
        }
        return require $path;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $app = config('app');
        return rtrim($app['base_url'], '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], string $layout = 'layouts/app'): string
    {
        return \App\Core\View::render($template, $data, $layout);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): \App\Core\Response
    {
        return \App\Core\Response::redirect($to);
    }
}
