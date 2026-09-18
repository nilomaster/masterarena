<?php
// Master Arena SaaS - Migration: Create Sports, Courts, Schedules and Pricing Tables
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // Table: modalidades (Sports types enabled per arena)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `modalidades` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `nome` VARCHAR(100) NOT NULL,
            `descricao` TEXT NULL,
            `icone` VARCHAR(50) NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_arena_modalidade` (`arena_id`, `nome`),
            INDEX `idx_modalidades_arena_ativo` (`arena_id`, `ativo`),
            CONSTRAINT `fk_modalidades_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: quadras (Courts / fields inside the arena)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `quadras` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `modalidade_id` BIGINT UNSIGNED NOT NULL,
            `nome` VARCHAR(100) NOT NULL,
            `descricao` TEXT NULL,
            `capacidade` INT NOT NULL DEFAULT 4,
            `valor_padrao` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('ATIVO', 'MANUTENCAO', 'INATIVO') NOT NULL DEFAULT 'ATIVO',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_quadras_arena_status` (`arena_id`, `status`),
            CONSTRAINT `fk_quadras_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_quadras_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: horarios (Operating hours configuration)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `horarios` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `quadra_id` BIGINT UNSIGNED NULL,
            `dia_semana` TINYINT UNSIGNED NOT NULL COMMENT '0=Sun, 1=Mon, ..., 6=Sat',
            `hora_inicio` TIME NOT NULL,
            `hora_fim` TIME NOT NULL,
            `duracao_minutos` INT NOT NULL DEFAULT 60,
            `intervalo_minutos` INT NOT NULL DEFAULT 0,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_horarios_arena_dia` (`arena_id`, `dia_semana`, `ativo`),
            CONSTRAINT `fk_horarios_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_horarios_quadra` FOREIGN KEY (`quadra_id`) REFERENCES `quadras` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: valores_horarios (Dynamic pricing rules)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `valores_horarios` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `quadra_id` BIGINT UNSIGNED NULL,
            `modalidade_id` BIGINT UNSIGNED NULL,
            `dia_semana` TINYINT UNSIGNED NOT NULL COMMENT '0=Sun, 1=Mon, ..., 6=Sat',
            `hora_inicio` TIME NOT NULL,
            `hora_fim` TIME NOT NULL,
            `valor` DECIMAL(10,2) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_valores_arena_busca` (`arena_id`, `dia_semana`, `hora_inicio`, `hora_fim`),
            CONSTRAINT `fk_valores_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_valores_quadra` FOREIGN KEY (`quadra_id`) REFERENCES `quadras` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_valores_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `valores_horarios`;");
        $pdo->exec("DROP TABLE IF EXISTS `horarios`;");
        $pdo->exec("DROP TABLE IF EXISTS `quadras`;");
        $pdo->exec("DROP TABLE IF EXISTS `modalidades`;");
    }
};
