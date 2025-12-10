<?php

namespace App\Core;

use PDO;

class Installer
{
    public function __construct(
        private Database $database,
        private string $installedFlag
    ) {
    }

    public function install(): ?string
    {
        if (file_exists($this->installedFlag)) {
            return null;
        }

        $pdo = $this->database->pdo();
        $driver = $this->database->driver();

        $this->createTables($pdo, $driver);
        $this->seed($pdo);

        file_put_contents($this->installedFlag, 'installed:' . date('c'));

        return 'Instalación automática completada. Se generó la base de datos y un usuario administrador (admin@local / admin123).';
    }

    private function createTables(PDO $pdo, string $driver): void
    {
        if ($driver === 'mysql') {
            $pdo->exec('CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $pdo->exec('CREATE TABLE IF NOT EXISTS screens (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                content TEXT NOT NULL,
                published_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        } else {
            $pdo->exec('CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                created_at TEXT NOT NULL
            )');

            $pdo->exec('CREATE TABLE IF NOT EXISTS screens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                published_at TEXT
            )');
        }
    }

    private function seed(PDO $pdo): void
    {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);

        $pdo->prepare('INSERT INTO users (name, email, password, created_at) VALUES (:name, :email, :password, :created_at)')
            ->execute([
                ':name' => 'Administrador',
                ':email' => 'admin@local',
                ':password' => $passwordHash,
                ':created_at' => date('c'),
            ]);

        $pdo->prepare('INSERT INTO screens (title, content, published_at) VALUES (:title, :content, :published_at)')
            ->execute([
                ':title' => 'Pantalla de bienvenida',
                ':content' => 'Proyecto MVC inicial listo para personalizar.',
                ':published_at' => date('c'),
            ]);
    }
}
