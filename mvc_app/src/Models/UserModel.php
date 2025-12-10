<?php

namespace App\Models;

use App\Core\Database;

class UserModel
{
    public function __construct(private Database $database)
    {
    }

    public function count(): int
    {
        $stmt = $this->database->pdo()->query('SELECT COUNT(*) as total FROM users');
        return (int) $stmt->fetchColumn();
    }

    public function all(): array
    {
        $stmt = $this->database->pdo()->query('SELECT id, name, email, created_at FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }
}
