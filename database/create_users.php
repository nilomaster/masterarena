<?php
// Master Arena SaaS - User Management & Database Config Web Utility
// Comments strictly in ASCII only.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../database/Connection.php';

use Database\Connection;

header('Content-Type: text/html; charset=utf-8');

$error = null;
$message = null;
$logs = [];
$dbConnected = false;
$pdo = null;

$envFile = dirname(__DIR__) . '/.env';

// Action: Save database credentials to .env
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save_db_config'])) {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_database'] ?? '');
    $dbUser = trim($_POST['db_username'] ?? '');
    $dbPass = (string)($_POST['db_password'] ?? '');

    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $testPdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        // Connection succeeded, update .env file
        $envContent = file_exists($envFile) ? file_get_contents($envFile) : '';
        
        $keysToUpdate = [
            'DB_HOST' => $dbHost,
            'DB_PORT' => $dbPort,
            'DB_DATABASE' => $dbName,
            'DB_USERNAME' => $dbUser,
            'DB_PASSWORD' => $dbPass,
        ];

        foreach ($keysToUpdate as $k => $v) {
            if (preg_match("/^{$k}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$k}=.*/m", "{$k}={$v}", $envContent);
            } else {
                $envContent .= "\n{$k}={$v}";
            }
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }

        file_put_contents($envFile, trim($envContent) . "\n");
        $message = "Conexao com o MySQL realizada com sucesso e salva no arquivo .env!";
    } catch (Throwable $e) {
        $error = "Falha ao conectar com os dados informados: " . $e->getMessage();
    }
}

// Check active database connection
try {
    $pdo = Connection::getInstance();
    $dbConnected = true;
} catch (Throwable $e) {
    if (!$error) {
        $error = "Falha ao conectar no banco de dados: " . $e->getMessage();
    }
}

// Function to ensure demo arena exists
function ensureDemoArena(PDO $pdo): int {
    $stmt = $pdo->prepare("SELECT `id` FROM `arenas` WHERE `slug` = 'arena-master-beach' LIMIT 1");
    $stmt->execute();
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int)$id;
    }

    $insert = $pdo->prepare("INSERT INTO `arenas` 
        (`slug`, `nome_arena`, `nome_fantasia`, `whatsapp`, `status`) 
        VALUES ('arena-master-beach', 'Arena Master Beach & Sports', 'Master Arena Clube', '11999998888', 'ATIVO')");
    $insert->execute();
    return (int)$pdo->lastInsertId();
}

// Function to create or update user
function upsertUser(PDO $pdo, ?int $arenaId, string $nome, string $email, string $senha, string $perfil, ?string $telefone = null): bool {
    $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);
    
    $check = $pdo->prepare("SELECT `id` FROM `usuarios` WHERE `email` = :email LIMIT 1");
    $check->execute([':email' => trim($email)]);
    $userId = $check->fetchColumn();

    if ($userId) {
        $stmt = $pdo->prepare("UPDATE `usuarios` 
            SET `arena_id` = :arena_id, `nome` = :nome, `senha_hash` = :hash, `perfil` = :perfil, `status` = 'ATIVO', `telefone` = :tel 
            WHERE `id` = :id");
        return $stmt->execute([
            ':arena_id' => $arenaId,
            ':nome' => $nome,
            ':hash' => $hash,
            ':perfil' => strtoupper($perfil),
            ':tel' => $telefone,
            ':id' => $userId,
        ]);
    }

    $stmt = $pdo->prepare("INSERT INTO `usuarios` 
        (`arena_id`, `nome`, `email`, `senha_hash`, `telefone`, `perfil`, `status`) 
        VALUES (:arena_id, :nome, :email, :hash, :tel, :perfil, 'ATIVO')");
    return $stmt->execute([
        ':arena_id' => $arenaId,
        ':nome' => $nome,
        ':email' => trim($email),
        ':hash' => $hash,
        ':tel' => $telefone,
        ':perfil' => strtoupper($perfil),
    ]);
}

// Action: Reset default users
if ($dbConnected && isset($_GET['action']) && $_GET['action'] === 'seed_defaults') {
    try {
        $arenaId = ensureDemoArena($pdo);

        upsertUser($pdo, null, 'Super Admin MasterDev', 'superadmin@masterarena.com.br', 'Admin@123456', 'SUPERADMIN', '11999990000');
        upsertUser($pdo, $arenaId, 'Administrador da Arena', 'admin@masterarena.com.br', 'Arena@123456', 'ADMIN', '11999991111');
        upsertUser($pdo, $arenaId, 'Atendente da Arena', 'atendente@masterarena.com.br', 'Staff@123456', 'FUNCIONARIO', '11999992222');

        $message = "3 usuarios padrao criados/atualizados com sucesso!";
    } catch (Throwable $e) {
        $error = "Erro ao processar: " . $e->getMessage();
    }
}

// Action: Custom user form submission
if ($dbConnected && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_create_user'])) {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = (string)($_POST['senha'] ?? '');
        $perfil = strtoupper(trim($_POST['perfil'] ?? 'ADMIN'));
        $arenaId = !empty($_POST['arena_id']) ? (int)$_POST['arena_id'] : null;
        $telefone = !empty($_POST['telefone']) ? trim($_POST['telefone']) : null;

        if (empty($nome) || empty($email) || empty($senha)) {
            throw new Exception("Preencha Nome, E-mail e Senha obrigatoriamente.");
        }

        if ($perfil !== 'SUPERADMIN' && empty($arenaId)) {
            $arenaId = ensureDemoArena($pdo);
        }

        upsertUser($pdo, $arenaId, $nome, $email, $senha, $perfil, $telefone);
        $message = "Usuario {$email} salvo com sucesso! Senha pronta para login.";
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Load registered users list
$usersList = [];
$arenasCount = 0;
$tablesExist = false;

if ($dbConnected) {
    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
        $tablesExist = count($tables) > 0;

        if ($tablesExist) {
            $stmt = $pdo->query("SELECT u.id, u.nome, u.email, u.senha_hash, u.perfil, u.status, u.arena_id, a.nome_arena 
                FROM `usuarios` u 
                LEFT JOIN `arenas` a ON u.arena_id = a.id 
                ORDER BY u.id ASC");
            $usersList = $stmt->fetchAll() ?: [];

            $arenasCount = (int)$pdo->query("SELECT COUNT(*) FROM `arenas`")->fetchColumn();
        }
    } catch (Throwable $e) {
        // ignore
    }
}

$curHost = getenv('DB_HOST') ?: '127.0.0.1';
$curPort = getenv('DB_PORT') ?: '3306';
$curDb   = getenv('DB_DATABASE') ?: 'masterarena';
$curUser = getenv('DB_USERNAME') ?: 'root';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MASTER ARENA — Gestao de Conexao & Usuarios</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #0a0e17; color: #f8fafc; padding: 2rem 1rem; line-height: 1.5; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        h1 { font-size: 1.5rem; color: #10b981; margin-bottom: 8px; }
        h2 { font-size: 1.15rem; color: #e2e8f0; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 0.95rem; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #86efac; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; }
        .btn { display: inline-block; padding: 10px 20px; font-size: 0.95rem; font-weight: 600; border-radius: 8px; cursor: pointer; text-decoration: none; border: none; transition: 0.2s; }
        .btn-green { background: #10b981; color: #041f13; font-weight: 700; }
        .btn-green:hover { background: #059669; }
        .btn-blue { background: #0284c7; color: #fff; }
        .btn-blue:hover { background: #0369a1; }
        .btn-orange { background: #ea580c; color: #fff; }
        .btn-orange:hover { background: #c2410c; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 0.9rem; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06); }
        th { background: rgba(255,255,255,0.03); color: #94a3b8; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-super { background: rgba(168, 85, 247, 0.2); color: #d8b4fe; border: 1px solid rgba(168, 85, 247, 0.4); }
        .badge-admin { background: rgba(14, 165, 233, 0.2); color: #7dd3fc; border: 1px solid rgba(14, 165, 233, 0.4); }
        .badge-staff { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.4); }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-size: 0.85rem; color: #94a3b8; font-weight: 500; }
        input, select { background: #0a0e17; border: 1px solid rgba(255,255,255,0.1); padding: 10px 12px; border-radius: 6px; color: #f8fafc; font-size: 0.95rem; }
        input:focus, select:focus { outline: none; border-color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>MASTER ARENA — Gestao de Banco de Dados & Usuarios</h1>
            <p style="color: #94a3b8; margin-bottom: 16px;">Configure a conexao do MySQL na hospedagem e gerencie as contas de acesso.</p>

            <?php if ($message): ?>
                <div class="alert alert-success">&#10004; <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">&#9888; <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="background: rgba(255,255,255,0.02); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                <p>
                    <strong>Status do Banco:</strong> 
                    <?= $dbConnected ? '<span style="color:#4ade80;">Conectado</span>' : '<span style="color:#f87171;">Desconectado (Connection refused)</span>' ?>
                    | <strong>Host Atual:</strong> <code><?= htmlspecialchars($curHost) ?></code>
                    | <strong>Base:</strong> <code><?= htmlspecialchars($curDb) ?></code>
                </p>
            </div>

            <?php if ($dbConnected && $tablesExist): ?>
                <div style="margin-bottom: 12px;">
                    <a href="?action=seed_defaults" class="btn btn-green">
                        &#9889; Criar / Redefinir Contas Padrao (1 Clique)
                    </a>
                    <span style="font-size: 0.85rem; color: #94a3b8; margin-left: 12px;">
                        Gera as contas padrao (superadmin, admin da arena e atendente)
                    </span>
                </div>
            <?php elseif ($dbConnected && !$tablesExist): ?>
                <div class="alert alert-error">
                    As tabelas ainda nao foram criadas nesta base do MySQL!
                    <div style="margin-top: 10px;">
                        <a href="/database/web_setup.php?action=run_migrations&seed=1" class="btn btn-orange">
                            &#9889; Criar Tabelas e Executar Seeds Agora
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Database Connection Configuration Form -->
        <div class="card">
            <h2>Configurar Conexao MySQL da Hospedagem (Locaweb)</h2>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 16px;">
                Na Locaweb, o MySQL nao fica em 127.0.0.1. Insira os dados fornecidos pelo painel da Locaweb (geralmente algo como <em>seudominio.mysql.dbaas.com.br</em>):
            </p>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Host do MySQL (DB_HOST)</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($curHost) ?>" placeholder="Ex: masterarena.mysql.dbaas.com.br" required>
                    </div>
                    <div class="form-group">
                        <label>Porta (DB_PORT)</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars($curPort) ?>" placeholder="3306" required>
                    </div>
                    <div class="form-group">
                        <label>Nome do Banco (DB_DATABASE)</label>
                        <input type="text" name="db_database" value="<?= htmlspecialchars($curDb) ?>" placeholder="Ex: masterarenaespor2" required>
                    </div>
                    <div class="form-group">
                        <label>Usuario do MySQL (DB_USERNAME)</label>
                        <input type="text" name="db_username" value="<?= htmlspecialchars($curUser) ?>" placeholder="Ex: masterarenaespor2" required>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Senha do MySQL (DB_PASSWORD)</label>
                        <input type="password" name="db_password" placeholder="Senha do banco criada na Locaweb" required>
                    </div>
                </div>
                <button type="submit" name="btn_save_db_config" class="btn btn-green">&#128190; Testar Conexao e Salvar no .env</button>
            </form>
        </div>

        <?php if ($dbConnected && $tablesExist): ?>
            <!-- User Creation Form -->
            <div class="card">
                <h2>Criar ou Atualizar Qualquer Usuario</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" placeholder="Ex: Carlos Silva" required>
                        </div>
                        <div class="form-group">
                            <label>E-mail de Login</label>
                            <input type="email" name="email" placeholder="seu@email.com" required>
                        </div>
                        <div class="form-group">
                            <label>Nova Senha</label>
                            <input type="text" name="senha" placeholder="Digite a senha desejada" required>
                        </div>
                        <div class="form-group">
                            <label>Perfil de Acesso</label>
                            <select name="perfil">
                                <option value="ADMIN">ADMIN (Gestor de Arena)</option>
                                <option value="SUPERADMIN">SUPERADMIN (Global do SaaS)</option>
                                <option value="FUNCIONARIO">FUNCIONARIO (Recepcao/Atendente)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="btn_create_user" class="btn btn-blue">&#10010; Salvar Usuario</button>
                </form>
            </div>

            <!-- Existing Users Table -->
            <div class="card">
                <h2>Usuarios Atualmente Cadastrados no Banco (<?= count($usersList) ?>)</h2>
                <?php if (empty($usersList)): ?>
                    <p style="color: #f87171; padding: 12px 0;">Nenhum usuario encontrado no banco. Clique no botao verde acima para gerar as contas padrao.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Arena Vinculada</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersList as $u): ?>
                                <tr>
                                    <td>#<?= (int)$u['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($u['nome']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php if ($u['perfil'] === 'SUPERADMIN'): ?>
                                            <span class="badge badge-super">SUPERADMIN</span>
                                        <?php elseif ($u['perfil'] === 'ADMIN'): ?>
                                            <span class="badge badge-admin">ADMIN</span>
                                        <?php else: ?>
                                            <span class="badge badge-staff">FUNCIONARIO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['nome_arena'] ?? ($u['arena_id'] ? "Arena #{$u['arena_id']}" : 'Global (Todas)')) ?></td>
                                    <td><span class="badge badge-active"><?= htmlspecialchars($u['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px;">
            <a href="/" style="color: #10b981; text-decoration: none; font-weight: 600;">&larr; Voltar para a Home do Master Arena</a>
        </div>
    </div>
</body>
</html>
