<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private array $config;
    private ?PDO $connection = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function driver(): string
    {
        return $this->config['driver'] ?? 'mysql';
    }

    public function pdo(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $driver = $this->driver();

        try {
            if ($driver === 'mysql') {
                $this->connection = $this->createMysqlConnection();
            } elseif ($driver === 'sqlite') {
                $this->connection = $this->createSqliteConnection();
            } else {
                throw new \InvalidArgumentException('Driver de base de datos no soportado: ' . $driver);
            }

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new \RuntimeException('No se pudo crear la conexión a la base de datos: ' . $exception->getMessage());
        }

        return $this->connection;
    }

    private function createMysqlConnection(): PDO
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = (int)($this->config['port'] ?? 3306);
        $database = $this->config['database'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        $pdo = new PDO($dsn, $username, $password, [
            PDO::MYSQL_ATTR_INIT_COMMAND => sprintf('SET NAMES %s COLLATE %s', $charset, $collation),
        ]);

        return $pdo;
    }

    private function createSqliteConnection(): PDO
    {
        $databasePath = $this->config['database'] ?? __DIR__ . '/../../storage/database.sqlite';
        $storageDir = dirname($databasePath);
        if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
            throw new \RuntimeException('No se pudo crear el directorio de almacenamiento para la base de datos.');
        }

        return new PDO('sqlite:' . $databasePath);
    }
}
