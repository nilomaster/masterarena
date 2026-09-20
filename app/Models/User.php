<?php
// Master Arena SaaS - User Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class User
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find user by email
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `usuarios` WHERE `email` = :email LIMIT 1");
        $stmt->execute([':email' => trim($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Find user by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `usuarios` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Verify password hash
    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    // Update last login timestamp
    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE `usuarios` SET `ultimo_login` = CURRENT_TIMESTAMP WHERE `id` = :id");
        $stmt->execute([':id' => $userId]);
    }

    // Remove sensitive password hash from array
    public static function formatSafe(array $user): array
    {
        unset($user['senha_hash']);
        return $user;
    }
}
