<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    private function __clone(): void
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('No se permite deserializar la conexión.');
    }

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require __DIR__ . '/config.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            self::$connection = new PDO(
                $dsn,
                $db['user'],
                $db['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
        } catch (PDOException $exception) {
            error_log('Error de conexión a MySQL: ' . $exception->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.', 0, $exception);
        }

        return self::$connection;
    }
}
