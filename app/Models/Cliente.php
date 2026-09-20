<?php
// Master Arena SaaS - Cliente (Customer) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Cliente
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find customer by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `clientes` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Find or automatically create a customer record based on contact details
    public function findOrCreateByContact(int $arenaId, array $data): int
    {
        $whatsapp = preg_replace('/\D/', '', (string)($data['whatsapp'] ?? ''));
        $cpf = !empty($data['cpf']) ? preg_replace('/\D/', '', (string)$data['cpf']) : null;
        $email = !empty($data['email']) ? trim((string)$data['email']) : null;
        $nome = trim((string)($data['nome'] ?? 'Cliente Avulso'));

        $existing = null;

        // Search by CPF within this arena
        if ($cpf) {
            $stmtCpf = $this->pdo->prepare("SELECT * FROM `clientes` WHERE `arena_id` = :arena_id AND `cpf` = :cpf LIMIT 1");
            $stmtCpf->execute([':arena_id' => $arenaId, ':cpf' => $cpf]);
            $existing = $stmtCpf->fetch();
        }

        // Search by WhatsApp within this arena if not found by CPF
        if (!$existing && $whatsapp) {
            $stmtWpp = $this->pdo->prepare("SELECT * FROM `clientes` WHERE `arena_id` = :arena_id AND `whatsapp` = :whatsapp LIMIT 1");
            $stmtWpp->execute([':arena_id' => $arenaId, ':whatsapp' => $whatsapp]);
            $existing = $stmtWpp->fetch();
        }

        if ($existing) {
            $clientId = (int)$existing['id'];
            // Optionally update missing fields
            $updateFields = [];
            $updateParams = [':id' => $clientId];

            if (empty($existing['cpf']) && $cpf) {
                $updateFields[] = "`cpf` = :cpf";
                $updateParams[':cpf'] = $cpf;
            }
            if (empty($existing['email']) && $email) {
                $updateFields[] = "`email` = :email";
                $updateParams[':email'] = $email;
            }

            if (!empty($updateFields)) {
                $sqlUp = "UPDATE `clientes` SET " . implode(', ', $updateFields) . " WHERE `id` = :id";
                $stmtUp = $this->pdo->prepare($sqlUp);
                $stmtUp->execute($updateParams);
            }

            return $clientId;
        }

        // Create new customer record
        $stmtIns = $this->pdo->prepare("INSERT INTO `clientes` (
            `arena_id`, `nome`, `telefone`, `whatsapp`, `cpf`, `email`, `observacao`
        ) VALUES (
            :arena_id, :nome, :telefone, :whatsapp, :cpf, :email, :observacao
        )");

        $telefone = !empty($data['telefone']) ? preg_replace('/\D/', '', (string)$data['telefone']) : null;
        $observacao = isset($data['observacao']) ? trim((string)$data['observacao']) : null;

        $stmtIns->execute([
            ':arena_id' => $arenaId,
            ':nome' => $nome,
            ':telefone' => $telefone,
            ':whatsapp' => $whatsapp ?: '00000000000',
            ':cpf' => $cpf,
            ':email' => $email,
            ':observacao' => $observacao,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // List customers for an arena with optional search filter and pagination
    public function listByArena(int $arenaId, ?string $search = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM `clientes` WHERE `arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (`nome` LIKE :search OR `whatsapp` LIKE :search OR `cpf` LIKE :search OR `email` LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= " ORDER BY `nome` ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    // Count total customers for an arena
    public function countByArena(int $arenaId, ?string $search = null): int
    {
        $sql = "SELECT COUNT(*) FROM `clientes` WHERE `arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (`nome` LIKE :search OR `whatsapp` LIKE :search OR `cpf` LIKE :search OR `email` LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}
