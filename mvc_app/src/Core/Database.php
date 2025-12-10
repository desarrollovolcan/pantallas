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

    public function pdo(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        if (($this->config['driver'] ?? '') !== 'sqlite') {
            throw new \InvalidArgumentException('Only sqlite is supported in this demo.');
        }

        $databasePath = $this->config['database'] ?? __DIR__ . '/../../storage/database.sqlite';
        $storageDir = dirname($databasePath);
        if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
            throw new \RuntimeException('No se pudo crear el directorio de almacenamiento para la base de datos.');
        }

        try {
            $this->connection = new PDO('sqlite:' . $databasePath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new \RuntimeException('No se pudo crear la conexión a la base de datos: ' . $exception->getMessage());
        }

        return $this->connection;
    }
}
