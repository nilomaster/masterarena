<?php
// Master Arena SaaS - Web Database Setup & Health Check
// Comments strictly in ASCII only.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/../database/Migration.php';
require_once __DIR__ . '/../database/seeds/InitialDatabaseSeeder.php';

use Database\Connection;
use Database\Seeds\InitialDatabaseSeeder;

header('Content-Type: text/html; charset=utf-8');

$action = $_GET['action'] ?? 'status';
$error = null;
$message = null;
$logs = [];

try {
    $config = require __DIR__ . '/../config/database.php';
    $pdo = Connection::getInstance();
    $dbConnected = true;
} catch (Throwable $e) {
    $dbConnected = false;
    $error = $e->getMessage();
}

if ($dbConnected && $action === 'run_migrations') {
    try {
        // Run migrations
        $files = glob(__DIR__ . '/migrations/*.php');
        sort($files);

        // Ensure migrations table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `batch` INT NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $stmt = $pdo->query("SELECT `migration` FROM `migrations`");
        $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxBatchStmt = $pdo->query("SELECT MAX(`batch`) FROM `migrations`");
        $batch = (int)$maxBatchStmt->fetchColumn() + 1;

        $count = 0;
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;
            $migration->up($pdo);

            $ins = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch)");
            $ins->execute([':migration' => $name, ':batch' => $batch]);
            $logs[] = "Migracao executada: " . htmlspecialchars($name);
            $count++;
        }

        // Run seeder if requested
        if (isset($_GET['seed']) && $_GET['seed'] === '1') {
            $seeder = new InitialDatabaseSeeder($pdo);
            $seeder->run();
            $logs[] = "Seeds iniciais executados com sucesso.";
        }

        $message = "Operacao realizada com sucesso! {$count} migracoes aplicadas.";
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Get list of tables
$tables = [];
if ($dbConnected) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        // ignore
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MASTER ARENA — Setup de Banco de Dados</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem 1rem; }
        .container { max-width: 760px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 2rem; border: 1px solid #334155; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); }
        h1 { font-size: 1.5rem; color: #38bdf8; margin-bottom: 0.5rem; }
        p.subtitle { color: #94a3b8; font-size: 0.95rem; margin-bottom: 1.5rem; }
        .card { background: #0f172a; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem; border: 1px solid #334155; }
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem; font-weight: bold; }
        .badge-success { background: #166534; color: #4ade80; }
        .badge-danger { background: #991b1b; color: #f87171; }
        .btn { display: inline-block; background: #0284c7; color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; font-size: 0.95rem; border: none; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #0369a1; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.95rem; }
        .alert-success { background: #14532d; color: #86efac; border: 1px solid #166534; }
        .alert-danger { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }
        ul { list-style: none; margin-top: 0.5rem; }
        li { padding: 0.35rem 0; color: #cbd5e1; font-size: 0.9rem; border-bottom: 1px solid #334155; }
        li:last-child { border-bottom: none; }
        code { background: #334155; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.85rem; color: #38bdf8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>MASTER ARENA — Instalador & Setup Web</h1>
        <p class="subtitle">Ferramenta para inicialização e migração do banco de dados na hospedagem.</p>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <strong>Sucesso:</strong> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Erro:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 0.5rem; font-size: 1.1rem;">Status da Conexão com o MySQL</h3>
            <p>
                Status: 
                <?php if ($dbConnected): ?>
                    <span class="badge badge-success">Conectado com Sucesso</span>
                <?php else: ?>
                    <span class="badge badge-danger">Falha na Conexão</span>
                <?php endif; ?>
            </p>
            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #94a3b8;">
                Host: <code><?= htmlspecialchars($config['host'] ?? '127.0.0.1') ?></code> | 
                Banco: <code><?= htmlspecialchars($config['database'] ?? 'masterarena') ?></code> | 
                Usuário: <code><?= htmlspecialchars($config['username'] ?? 'root') ?></code>
            </div>
        </div>

        <?php if ($dbConnected): ?>
            <div class="card">
                <h3 style="margin-bottom: 0.5rem; font-size: 1.1rem;">Tabelas Atuais no Banco (<?= count($tables) ?>)</h3>
                <?php if (count($tables) > 0): ?>
                    <ul style="max-height: 180px; overflow-y: auto;">
                        <?php foreach ($tables as $t): ?>
                            <li>✓ <?= htmlspecialchars($t) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #94a3b8; font-size: 0.9rem;">Nenhuma tabela encontrada. Clique no botão abaixo para criar todas as 18 tabelas e dados iniciais.</p>
                <?php endif; ?>
            </div>

            <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
                <a href="?action=run_migrations&seed=1" class="btn">Executar Migrations e Dados Iniciais (Seeds)</a>
            </div>
        <?php else: ?>
            <div class="card">
                <h3 style="margin-bottom: 0.5rem; color: #f87171;">Atenção: Banco de Dados não conectado</h3>
                <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.5;">
                    Crie o banco de dados e usuário no seu cPanel e configure o arquivo <code>.env</code> (ou edite diretamente <code>config/database.php</code>) com o nome do banco, usuário e senha criados.
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($logs)): ?>
            <div class="card" style="margin-top: 1.25rem;">
                <h4 style="margin-bottom: 0.5rem; font-size: 0.95rem; color: #38bdf8;">Logs de Execução:</h4>
                <ul>
                    <?php foreach ($logs as $l): ?>
                        <li><?= $l ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
