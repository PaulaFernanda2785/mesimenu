<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use Throwable;

final class ExceptionHandler
{
    public static function render(Throwable $e): Response
    {
        $app = require BASE_PATH . '/config/app.php';
        $status = 500;
        $message = 'Erro interno do servidor.';

        if ($e instanceof HttpException) {
            $status = $e->statusCode();
            $message = $e->getMessage();
        } elseif (($app['debug'] ?? false) === true) {
            $message = $e->getMessage();
        }

        self::log($e);

        return Response::make(
            View::render('errors/generic', [
                'title' => 'Erro',
                'status' => $status,
                'message' => $message,
            ], 'layouts/auth'),
            $status
        );
    }

    private static function log(Throwable $e): void
    {
        $dir = BASE_PATH . '/storage/logs';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf(
            "[%s] %s: %s in %s:%d%s",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            PHP_EOL
        );

        file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
    }
}
