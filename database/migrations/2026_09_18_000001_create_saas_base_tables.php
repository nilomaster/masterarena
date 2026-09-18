<?php
// Master Arena SaaS - Migration: Create SaaS Base Tables
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // Table: planos (SaaS subscription plans)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `planos` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `descricao` TEXT NULL,
            `preco_mensal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `limite_quadras` INT NOT NULL DEFAULT 3,
            `limite_usuarios` INT NOT NULL DEFAULT 5,
            `limite_agendamentos` INT NOT NULL DEFAULT 500,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: arenas (Multi-tenant arenas)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `arenas` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `nome_arena` VARCHAR(150) NOT NULL,
            `nome_fantasia` VARCHAR(150) NULL,
            `cnpj` VARCHAR(20) NULL,
            `telefone` VARCHAR(25) NULL,
            `whatsapp` VARCHAR(25) NOT NULL,
            `email` VARCHAR(150) NULL,
            `endereco` VARCHAR(255) NULL,
            `numero` VARCHAR(20) NULL,
            `bairro` VARCHAR(100) NULL,
            `cidade` VARCHAR(100) NULL,
            `estado` VARCHAR(2) NULL,
            `cep` VARCHAR(15) NULL,
            `logo` VARCHAR(255) NULL,
            `instagram` VARCHAR(150) NULL,
            `facebook` VARCHAR(150) NULL,
            `site` VARCHAR(255) NULL,
            `google_maps_url` TEXT NULL,
            `descricao` TEXT NULL,
            `status` ENUM('ATIVO', 'BLOQUEADO', 'INATIVO') NOT NULL DEFAULT 'ATIVO',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_arenas_slug` (`slug`),
            INDEX `idx_arenas_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: assinaturas (Arena plan subscriptions)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `assinaturas` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `plano_id` BIGINT UNSIGNED NOT NULL,
            `data_inicio` DATE NOT NULL,
            `data_expiracao` DATE NOT NULL,
            `status` ENUM('ATIVA', 'CANCELADA', 'EXPIRADA', 'PENDENTE') NOT NULL DEFAULT 'ATIVA',
            `limite_quadras` INT NOT NULL,
            `limite_usuarios` INT NOT NULL,
            `limite_agendamentos` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_assinaturas_arena` (`arena_id`),
            INDEX `idx_assinaturas_status` (`status`),
            CONSTRAINT `fk_assinaturas_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_assinaturas_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: configuracoes_arena (Arena settings key-value store)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `configuracoes_arena` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `chave` VARCHAR(100) NOT NULL,
            `valor` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_arena_chave` (`arena_id`, `chave`),
            CONSTRAINT `fk_configuracoes_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `configuracoes_arena`;");
        $pdo->exec("DROP TABLE IF EXISTS `assinaturas`;");
        $pdo->exec("DROP TABLE IF EXISTS `arenas`;");
        $pdo->exec("DROP TABLE IF EXISTS `planos`;");
    }
};
