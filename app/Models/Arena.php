<?php
// Master Arena SaaS - Arena Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Arena
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find arena by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `arenas` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Find arena by URL slug
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `arenas` WHERE `slug` = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Check if arena is currently active
    public function isActive(array $arena): bool
    {
        return ($arena['status'] ?? '') === 'ATIVO';
    }
}
