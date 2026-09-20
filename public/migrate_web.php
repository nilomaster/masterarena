<?php
// Master Arena SaaS - Web Migration Runner for Hosting Environments
// Comments strictly in ASCII only.

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/../database/Migration.php';

use Database\Connection;

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = Connection::getInstance();

    // 1. Ensure migrations control table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `batch` INT NOT NULL,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Scan migration files
    $migrationsDir = __DIR__ . '/../database/migrations';
    $files = glob($migrationsDir . '/*.php');
    sort($files);

    // 3. Get already applied migrations
    $stmt = $pdo->query("SELECT `migration` FROM `migrations` ORDER BY `id` ASC");
    $applied = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // 4. Calculate next batch
    $stmtBatch = $pdo->query("SELECT MAX(`batch`) as max_batch FROM `migrations`");
    $maxBatch = $stmtBatch->fetchColumn();
    $nextBatch = ($maxBatch !== null && $maxBatch !== false) ? ((int)$maxBatch + 1) : 1;

    $executed = [];
    $errors = [];

    foreach ($files as $file) {
        $name = basename($file);
        if (!in_array($name, $applied)) {
            try {
                $migration = require $file;
                if ($migration instanceof \Database\Migration) {
                    $migration->up($pdo);
                    $ins = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch) ON DUPLICATE KEY UPDATE `migration` = `migration`");
                    $ins->execute([
                        ':migration' => $name,
                        ':batch' => $nextBatch,
                    ]);
                    $executed[] = $name;
                }
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                // Check if error is due to pre-existing database objects (tables, columns, constraints)
                $isAlreadyExists = str_contains($msg, '1022') || // duplicate key in table / constraint
                                   str_contains($msg, '1050') || // table already exists
                                   str_contains($msg, '1060') || // duplicate column name
                                   str_contains($msg, '1061') || // duplicate key name
                                   str_contains($msg, '1826');   // duplicate foreign key constraint name

                if ($isAlreadyExists) {
                    // Mark as already applied since the schema is already present in database
                    $ins = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch) ON DUPLICATE KEY UPDATE `migration` = `migration`");
                    $ins->execute([
                        ':migration' => $name,
                        ':batch' => $nextBatch,
                    ]);
                    $executed[] = "{$name} (Estrutura ja existia no banco - sincronizado com sucesso)";
                } else {
                    $errors[] = "Erro em {$name}: " . $e->getMessage();
                    break;
                }
            }
        }
    }
} catch (Throwable $e) {
    $errors[] = "Erro de conexao com o banco de dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Arena — Atualização de Banco de Dados</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0a0e17;
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background: rgba(18, 26, 43, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 32px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 1.4rem;
            margin-bottom: 8px;
            color: #00f279;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        p {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 24px;
        }
        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #00f279;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 16px;
        }
        .badge-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 16px;
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        li {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 6px;
            font-size: 0.82rem;
            font-family: monospace;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #00f279, #10b981);
            color: #031509;
            text-decoration: none;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>⚡ Master Arena — Migrations</h1>
        <p>Atualização estrutural do banco de dados na hospedagem.</p>

        <?php if (!empty($errors)): ?>
            <div class="badge-error">
                <strong>Ocorreu uma falha ao aplicar as migrações:</strong>
                <ul style="margin-top: 10px;">
                    <?php foreach ($errors as $err): ?>
                        <li>❌ <?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (!empty($executed)): ?>
            <div class="badge-success">
                <strong>Sucesso! Lote #<?= $nextBatch ?> executado com sucesso:</strong>
            </div>
            <ul>
                <?php foreach ($executed as $m): ?>
                    <li>✅ <?= htmlspecialchars($m) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="badge-success">
                <strong>Tudo atualizado!</strong> Nenhuma migração pendente encontrada. Todas as tabelas já estão sincronizadas.
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="/dashboard" class="btn">Ir para o Dashboard &rarr;</a>
        </div>
    </div>
</body>
</html>
