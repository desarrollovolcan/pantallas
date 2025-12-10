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

        $this->seed($pdo);

        file_put_contents($this->installedFlag, 'installed:' . date('c'));

        return 'Instalación automática completada. Se generó la base de datos y un usuario administrador (admin@local / admin123).';
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
