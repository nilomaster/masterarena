<?php
// Master Arena SaaS - Migration: Create Clients Table
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // Table: clientes (Arena clients/customers)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `clientes` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `nome` VARCHAR(150) NOT NULL,
            `telefone` VARCHAR(25) NULL,
            `whatsapp` VARCHAR(25) NOT NULL,
            `cpf` VARCHAR(20) NULL,
            `email` VARCHAR(150) NULL,
            `observacao` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_clientes_arena_nome` (`arena_id`, `nome`),
            INDEX `idx_clientes_arena_whatsapp` (`arena_id`, `whatsapp`),
            INDEX `idx_clientes_arena_cpf` (`arena_id`, `cpf`),
            CONSTRAINT `fk_clientes_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `clientes`;");
    }
};
