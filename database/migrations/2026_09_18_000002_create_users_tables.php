<?php
// Master Arena SaaS - Migration: Create Users and Audit Log Tables
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // Table: usuarios (System users: SUPERADMIN, ADMIN, FUNCIONARIO)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NULL,
            `nome` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `senha_hash` VARCHAR(255) NOT NULL,
            `telefone` VARCHAR(25) NULL,
            `perfil` ENUM('SUPERADMIN', 'ADMIN', 'FUNCIONARIO') NOT NULL DEFAULT 'FUNCIONARIO',
            `status` ENUM('ATIVO', 'INATIVO', 'BLOQUEADO') NOT NULL DEFAULT 'ATIVO',
            `ultimo_login` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_usuarios_email` (`email`),
            INDEX `idx_usuarios_arena_perfil` (`arena_id`, `perfil`),
            INDEX `idx_usuarios_status` (`status`),
            CONSTRAINT `fk_usuarios_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: logs (Audit trail)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NULL,
            `usuario_id` BIGINT UNSIGNED NULL,
            `acao` VARCHAR(50) NOT NULL,
            `entidade` VARCHAR(50) NOT NULL,
            `entidade_id` BIGINT UNSIGNED NULL,
            `detalhes` LONGTEXT NULL,
            `ip` VARCHAR(45) NULL,
            `user_agent` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_logs_arena` (`arena_id`),
            INDEX `idx_logs_usuario` (`usuario_id`),
            INDEX `idx_logs_acao` (`acao`),
            INDEX `idx_logs_created_at` (`created_at`),
            CONSTRAINT `fk_logs_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `logs`;");
        $pdo->exec("DROP TABLE IF EXISTS `usuarios`;");
    }
};
