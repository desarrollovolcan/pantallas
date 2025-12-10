<?php

namespace App\Models;

use App\Core\Database;

class ScreenModel
{
    public function __construct(private Database $database)
    {
    }

    public function count(): int
    {
        $stmt = $this->database->pdo()->query('SELECT COUNT(*) as total FROM screens');
        return (int) $stmt->fetchColumn();
    }

    public function all(): array
    {
        $stmt = $this->database->pdo()->query('SELECT id, title, content, published_at FROM screens ORDER BY published_at DESC');
        return $stmt->fetchAll();
    }
}
