<?php
// Master Arena SaaS - Arena Model and Multi-Tenant Store
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
        $stmt->execute([':slug' => trim($slug)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Check if arena is currently active
    public function isActive(array $arena): bool
    {
        return ($arena['status'] ?? '') === 'ATIVO';
    }

    // Check if slug is available for creation or update
    public function isSlugAvailable(string $slug, ?int $exceptId = null): bool
    {
        $sql = "SELECT `id` FROM `arenas` WHERE `slug` = :slug";
        $params = [':slug' => trim($slug)];

        if ($exceptId !== null) {
            $sql .= " AND `id` != :except_id";
            $params[':except_id'] = $exceptId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() === false;
    }

    // List all arenas with optional status filter and pagination
    public function listAll(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM `arenas`";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE `status` = :status";
            $params[':status'] = strtoupper($status);
        }

        $sql .= " ORDER BY `id` DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    // Count total arenas with optional status filter
    public function countAll(?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM `arenas`";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE `status` = :status";
            $params[':status'] = strtoupper($status);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    // Create a new arena record
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `arenas` (
            `slug`, `nome_arena`, `nome_fantasia`, `cnpj`, `telefone`, `whatsapp`, 
            `email`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, 
            `logo`, `instagram`, `facebook`, `site`, `google_maps_url`, `descricao`, `status`
        ) VALUES (
            :slug, :nome_arena, :nome_fantasia, :cnpj, :telefone, :whatsapp, 
            :email, :endereco, :numero, :bairro, :cidade, :estado, :cep, 
            :logo, :instagram, :facebook, :site, :google_maps_url, :descricao, :status
        )");

        $stmt->execute([
            ':slug' => trim($data['slug']),
            ':nome_arena' => trim($data['nome_arena']),
            ':nome_fantasia' => isset($data['nome_fantasia']) ? trim($data['nome_fantasia']) : null,
            ':cnpj' => isset($data['cnpj']) ? preg_replace('/\D/', '', $data['cnpj']) : null,
            ':telefone' => isset($data['telefone']) ? trim($data['telefone']) : null,
            ':whatsapp' => trim($data['whatsapp']),
            ':email' => isset($data['email']) ? trim($data['email']) : null,
            ':endereco' => isset($data['endereco']) ? trim($data['endereco']) : null,
            ':numero' => isset($data['numero']) ? trim($data['numero']) : null,
            ':bairro' => isset($data['bairro']) ? trim($data['bairro']) : null,
            ':cidade' => isset($data['cidade']) ? trim($data['cidade']) : null,
            ':estado' => isset($data['estado']) ? strtoupper(substr(trim($data['estado']), 0, 2)) : null,
            ':cep' => isset($data['cep']) ? preg_replace('/\D/', '', $data['cep']) : null,
            ':logo' => isset($data['logo']) ? trim($data['logo']) : null,
            ':instagram' => isset($data['instagram']) ? trim($data['instagram']) : null,
            ':facebook' => isset($data['facebook']) ? trim($data['facebook']) : null,
            ':site' => isset($data['site']) ? trim($data['site']) : null,
            ':google_maps_url' => isset($data['google_maps_url']) ? trim($data['google_maps_url']) : null,
            ':descricao' => isset($data['descricao']) ? trim($data['descricao']) : null,
            ':status' => isset($data['status']) ? strtoupper($data['status']) : 'ATIVO',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Update arena record
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedColumns = [
            'slug', 'nome_arena', 'nome_fantasia', 'cnpj', 'telefone', 'whatsapp',
            'email', 'endereco', 'numero', 'bairro', 'cidade', 'estado', 'cep',
            'logo', 'instagram', 'facebook', 'site', 'google_maps_url', 'descricao'
        ];

        foreach ($allowedColumns as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "`{$col}` = :{$col}";
                $val = $data[$col];
                if ($col === 'cnpj' || $col === 'cep') {
                    $val = $val !== null ? preg_replace('/\D/', '', (string)$val) : null;
                } elseif ($col === 'estado' && $val !== null) {
                    $val = strtoupper(substr(trim((string)$val), 0, 2));
                } elseif ($val !== null) {
                    $val = trim((string)$val);
                }
                $params[":{$col}"] = $val;
            }
        }

        if (empty($fields)) {
            return true;
        }

        $sql = "UPDATE `arenas` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Update operational status
    public function updateStatus(int $id, string $status): bool
    {
        $status = strtoupper($status);
        if (!in_array($status, ['ATIVO', 'BLOQUEADO', 'INATIVO'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE `arenas` SET `status` = :status WHERE `id` = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // Get all settings key-value pairs for an arena
    public function getSettings(int $arenaId): array
    {
        $stmt = $this->pdo->prepare("SELECT `chave`, `valor` FROM `configuracoes_arena` WHERE `arena_id` = :arena_id");
        $stmt->execute([':arena_id' => $arenaId]);
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['chave']] = $row['valor'];
        }

        return $settings;
    }

    // Set a single setting for an arena
    public function setSetting(int $arenaId, string $key, $value): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO `configuracoes_arena` (`arena_id`, `chave`, `valor`)
            VALUES (:arena_id, :chave, :valor)
            ON DUPLICATE KEY UPDATE `valor` = :valor_update, `updated_at` = CURRENT_TIMESTAMP");

        $valStr = is_scalar($value) ? (string)$value : json_encode($value);
        return $stmt->execute([
            ':arena_id' => $arenaId,
            ':chave' => trim($key),
            ':valor' => $valStr,
            ':valor_update' => $valStr,
        ]);
    }

    // Set multiple settings in batch
    public function setManySettings(int $arenaId, array $settings): bool
    {
        foreach ($settings as $key => $value) {
            $this->setSetting($arenaId, (string)$key, $value);
        }
        return true;
    }

    // Format safe public representation for arena public portal
    public static function formatSafePublic(array $arena): array
    {
        return [
            'id' => (int)$arena['id'],
            'slug' => $arena['slug'],
            'nome_arena' => $arena['nome_arena'],
            'nome_fantasia' => $arena['nome_fantasia'] ?? $arena['nome_arena'],
            'whatsapp' => $arena['whatsapp'],
            'telefone' => $arena['telefone'] ?? null,
            'email' => $arena['email'] ?? null,
            'endereco' => $arena['endereco'] ?? null,
            'numero' => $arena['numero'] ?? null,
            'bairro' => $arena['bairro'] ?? null,
            'cidade' => $arena['cidade'] ?? null,
            'estado' => $arena['estado'] ?? null,
            'cep' => $arena['cep'] ?? null,
            'logo' => $arena['logo'] ?? null,
            'instagram' => $arena['instagram'] ?? null,
            'facebook' => $arena['facebook'] ?? null,
            'site' => $arena['site'] ?? null,
            'google_maps_url' => $arena['google_maps_url'] ?? null,
            'descricao' => $arena['descricao'] ?? null,
            'status' => $arena['status'],
        ];
    }
}
