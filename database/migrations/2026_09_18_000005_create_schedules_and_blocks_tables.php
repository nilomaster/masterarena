<?php
// Master Arena SaaS - Migration: Create Schedules, Schedule History and Blocks Tables
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // Table: agendamentos (Bookings / appointments)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `agendamentos` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `quadra_id` BIGINT UNSIGNED NOT NULL,
            `cliente_id` BIGINT UNSIGNED NOT NULL,
            `data` DATE NOT NULL,
            `hora_inicio` TIME NOT NULL,
            `hora_fim` TIME NOT NULL,
            `valor_original` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `desconto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `valor_final` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `cupom_id` BIGINT UNSIGNED NULL,
            `status` ENUM('PENDENTE', 'CONFIRMADO', 'CANCELADO', 'CONCLUIDO', 'BLOQUEADO') NOT NULL DEFAULT 'PENDENTE',
            `observacao` TEXT NULL,
            `criado_por` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_agendamentos_conflict` (`arena_id`, `quadra_id`, `data`, `hora_inicio`, `hora_fim`, `status`),
            INDEX `idx_agendamentos_data_status` (`arena_id`, `data`, `status`),
            INDEX `idx_agendamentos_cliente` (`arena_id`, `cliente_id`),
            CONSTRAINT `fk_agendamentos_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_agendamentos_quadra` FOREIGN KEY (`quadra_id`) REFERENCES `quadras` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_agendamentos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_agendamentos_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: agendamento_historico (Audit and change log for bookings)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `agendamento_historico` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `agendamento_id` BIGINT UNSIGNED NOT NULL,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `usuario_id` BIGINT UNSIGNED NULL,
            `acao` VARCHAR(50) NOT NULL,
            `status_anterior` VARCHAR(30) NULL,
            `status_novo` VARCHAR(30) NOT NULL,
            `motivo` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_historico_agendamento` (`agendamento_id`),
            INDEX `idx_historico_arena` (`arena_id`),
            CONSTRAINT `fk_historico_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_historico_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_historico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: bloqueios (Schedule blocks for maintenance, tournaments or private events)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `bloqueios` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `quadra_id` BIGINT UNSIGNED NOT NULL,
            `data_inicio` DATE NOT NULL,
            `data_fim` DATE NOT NULL,
            `hora_inicio` TIME NOT NULL,
            `hora_fim` TIME NOT NULL,
            `motivo` VARCHAR(255) NOT NULL,
            `criado_por` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_bloqueios_busca` (`arena_id`, `quadra_id`, `data_inicio`, `data_fim`),
            CONSTRAINT `fk_bloqueios_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_bloqueios_quadra` FOREIGN KEY (`quadra_id`) REFERENCES `quadras` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_bloqueios_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `bloqueios`;");
        $pdo->exec("DROP TABLE IF EXISTS `agendamento_historico`;");
        $pdo->exec("DROP TABLE IF EXISTS `agendamentos`;");
    }
};
