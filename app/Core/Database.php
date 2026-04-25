<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require BASE_PATH . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
        self::applySessionTimezone(self::$connection, (string) ($config['timezone'] ?? '-03:00'));

        return self::$connection;
    }

    private static function applySessionTimezone(PDO $connection, string $timezone): void
    {
        $timezone = trim($timezone);
        if ($timezone === '') {
            $timezone = '-03:00';
        }

        if (preg_match('/^[+-](0\d|1[0-4]):[0-5]\d$/', $timezone) !== 1 && strtoupper($timezone) !== 'SYSTEM') {
            $timezone = '-03:00';
        }

        $connection->exec("SET time_zone = " . $connection->quote($timezone));
    }
}
