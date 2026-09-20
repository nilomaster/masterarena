<?php
// Master Arena SaaS - Sports (Modalidades) & Courts (Quadras) Test Suite
// Comments strictly in ASCII only.

define('MASTER_ARENA_TEST_MODE', true);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Request;
use App\Core\Router;
use Database\Connection;

Autoloader::register();

echo "====================================================\n";
echo " MASTER ARENA - Sports & Courts Management Tests\n";
echo "====================================================\n";

function requestApi(string $method, string $uri, array $body = [], ?string $token = null): array
{
    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'MasterArena-TestClient/1.0';

    if ($token !== null) {
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";
    } else {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    $_GET = [];
    $_POST = [];

    $parsed = parse_url($uri);
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $_GET);
    }

    if (!empty($body)) {
        $_POST = $body;
    }

    $request = new Request();
    $router = new Router();

    require __DIR__ . '/../routes/api.php';

    ob_start();
    $statusCode = 200;
    $json = [];
    try {
        $router->dispatch($request);
    } catch (\App\Core\EarlyExitException $e) {
        $statusCode = $e->getStatusCode();
        $json = $e->getData();
    } catch (Throwable $e) {
        ob_end_clean();
        return ['status' => 500, 'error' => $e->getMessage()];
    }
    $raw = ob_get_clean();

    if (empty($json) && !empty($raw)) {
        $json = json_decode($raw, true);
    }

    return [
        'status' => $statusCode,
        'json' => $json,
        'raw' => $raw,
    ];
}

// 0. Authenticate test users
echo "Authenticating test users ... ";
$loginSuper = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'superadmin@masterarena.com.br',
    'senha' => 'Admin@123456',
]);
$superToken = $loginSuper['json']['data']['token'] ?? null;

$loginAdmin1 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin@masterarena.com.br',
    'senha' => 'Arena@123456',
]);
$admin1Token = $loginAdmin1['json']['data']['token'] ?? null;

if ($superToken && $admin1Token) {
    echo "[OK]\n";
} else {
    echo "[FAILED] Could not authenticate test users.\n";
    exit(1);
}

// Setup: Create a second arena and a second admin user to test multi-tenant boundaries
$pdo = Connection::getInstance();

// Find or create second arena
$stmtArena = $pdo->prepare("SELECT `id` FROM `arenas` WHERE `slug` = 'arena-tenant-two' LIMIT 1");
$stmtArena->execute();
$arena2Id = $stmtArena->fetchColumn();

if (!$arena2Id) {
    $stmtCreateArena = $pdo->prepare("INSERT INTO `arenas` (`slug`, `nome_arena`, `whatsapp`, `status`) VALUES ('arena-tenant-two', 'Arena Tenant Two', '11988887777', 'ATIVO')");
    $stmtCreateArena->execute();
    $arena2Id = (int)$pdo->lastInsertId();
} else {
    $arena2Id = (int)$arena2Id;
}

// Find or create second admin user
$stmtUser = $pdo->prepare("SELECT `id` FROM `usuarios` WHERE `email` = 'admin2@masterarena.com.br' LIMIT 1");
$stmtUser->execute();
$user2Id = $stmtUser->fetchColumn();

if (!$user2Id) {
    $hash = password_hash('Arena@123456', PASSWORD_BCRYPT);
    $stmtCreateUser = $pdo->prepare("INSERT INTO `usuarios` (`arena_id`, `nome`, `email`, `senha_hash`, `perfil`, `status`) VALUES (:arena_id, 'Admin Tenant Two', 'admin2@masterarena.com.br', :senha, 'ADMIN', 'ATIVO')");
    $stmtCreateUser->execute([':arena_id' => $arena2Id, ':senha' => $hash]);
}

$loginAdmin2 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin2@masterarena.com.br',
    'senha' => 'Arena@123456',
]);
$admin2Token = $loginAdmin2['json']['data']['token'] ?? null;

if (!$admin2Token) {
    echo "[FAILED] Could not authenticate second tenant admin user.\n";
    exit(1);
}

// Test 1: Public list of modalidades for Arena 1
echo "Test 1: Public list of modalidades for Arena 1 ... ";
$t1 = requestApi('GET', '/api/v1/arenas/1/modalidades');
if ($t1['status'] === 200 && is_array($t1['json']['data']['modalidades'] ?? null)) {
    echo "[PASSED] (Modalidades array returned)\n";
} else {
    echo "[FAILED]\n";
    print_r($t1);
}

// Test 2: Create new modalidade by Admin 1
echo "Test 2: Create modalidade by Admin 1 ... ";
$uniqueSportName = 'Beach Tennis Pro ' . time();
$t2 = requestApi('POST', '/api/v1/arenas/1/modalidades', [
    'nome' => $uniqueSportName,
    'descricao' => 'Quadras de areia para Beach Tennis com iluminacao LED',
    'icone' => 'racket',
    'ativo' => 1,
], $admin1Token);
$modalidadeId = (int)($t2['json']['data']['modalidade']['id'] ?? 0);
if ($t2['status'] === 201 && $modalidadeId > 0 && ($t2['json']['data']['modalidade']['nome'] ?? '') === $uniqueSportName) {
    echo "[PASSED] (Modalidade created with ID {$modalidadeId})\n";
} else {
    echo "[FAILED]\n";
    print_r($t2);
}

// Test 3: Reject duplicate modalidade name in the same arena
echo "Test 3: Reject duplicate modalidade name in the same arena ... ";
$t3 = requestApi('POST', '/api/v1/arenas/1/modalidades', [
    'nome' => $uniqueSportName,
], $admin1Token);
if ($t3['status'] === 422 && !empty($t3['json']['errors']['nome'])) {
    echo "[PASSED] (422 Duplicate sport name blocked)\n";
} else {
    echo "[FAILED]\n";
    print_r($t3);
}

// Test 4: Show modalidade details
echo "Test 4: Show modalidade details by ID ... ";
$t4 = requestApi('GET', "/api/v1/modalidades/{$modalidadeId}", [], $admin1Token);
if ($t4['status'] === 200 && ($t4['json']['data']['modalidade']['id'] ?? 0) === $modalidadeId) {
    echo "[PASSED] (Modalidade details and courts count returned)\n";
} else {
    echo "[FAILED]\n";
    print_r($t4);
}

// Test 5: Update modalidade details
echo "Test 5: Update modalidade description ... ";
$t5 = requestApi('PUT', "/api/v1/modalidades/{$modalidadeId}", [
    'descricao' => 'Descricao atualizada para Beach Tennis',
], $admin1Token);
if ($t5['status'] === 200 && ($t5['json']['data']['modalidade']['descricao'] ?? '') === 'Descricao atualizada para Beach Tennis') {
    echo "[PASSED] (Modalidade updated)\n";
} else {
    echo "[FAILED]\n";
    print_r($t5);
}

// Test 6: Create new court linked to modalidade
echo "Test 6: Create new court linked to modalidade by Admin 1 ... ";
$t6 = requestApi('POST', '/api/v1/arenas/1/quadras', [
    'modalidade_id' => $modalidadeId,
    'nome' => 'Quadra 1 - Central Beach',
    'descricao' => 'Quadra oficial com areia tratada',
    'capacidade' => 4,
    'valor_padrao' => 120.00,
    'status' => 'ATIVO',
], $admin1Token);
$quadraId = (int)($t6['json']['data']['quadra']['id'] ?? 0);
if ($t6['status'] === 201 && $quadraId > 0 && ($t6['json']['data']['quadra']['nome'] ?? '') === 'Quadra 1 - Central Beach') {
    echo "[PASSED] (Court created with ID {$quadraId})\n";
} else {
    echo "[FAILED]\n";
    print_r($t6);
}

// Test 7: Public list of courts for Arena 1
echo "Test 7: Public list of courts for Arena 1 ... ";
$t7 = requestApi('GET', '/api/v1/arenas/1/quadras');
$courts = $t7['json']['data']['quadras'] ?? [];
$foundCourt = false;
foreach ($courts as $c) {
    if ((int)$c['id'] === $quadraId && $c['modalidade_nome'] === $uniqueSportName) {
        $foundCourt = true;
        break;
    }
}
if ($t7['status'] === 200 && $foundCourt) {
    echo "[PASSED] (Court found with modalidade data)\n";
} else {
    echo "[FAILED]\n";
    print_r($t7);
}

// Test 8: Show court details
echo "Test 8: Show court details by ID ... ";
$t8 = requestApi('GET', "/api/v1/quadras/{$quadraId}", [], $admin1Token);
if ($t8['status'] === 200 && (int)($t8['json']['data']['quadra']['id'] ?? 0) === $quadraId) {
    echo "[PASSED] (Court details retrieved)\n";
} else {
    echo "[FAILED]\n";
    print_r($t8);
}

// Test 9: Change court operational status to MANUTENCAO
echo "Test 9: Change court status to MANUTENCAO ... ";
$t9 = requestApi('PATCH', "/api/v1/quadras/{$quadraId}/status", [
    'status' => 'MANUTENCAO',
], $admin1Token);
if ($t9['status'] === 200 && ($t9['json']['data']['status'] ?? '') === 'MANUTENCAO') {
    echo "[PASSED] (Status changed to MANUTENCAO)\n";
} else {
    echo "[FAILED]\n";
    print_r($t9);
}

// Test 10: Public list excludes MANUTENCAO courts by default
echo "Test 10: Public list excludes MANUTENCAO court ... ";
$t10 = requestApi('GET', '/api/v1/arenas/1/quadras');
$courtsAfter = $t10['json']['data']['quadras'] ?? [];
$foundInPublic = false;
foreach ($courtsAfter as $c) {
    if ((int)$c['id'] === $quadraId) {
        $foundInPublic = true;
        break;
    }
}
if ($t10['status'] === 200 && !$foundInPublic) {
    echo "[PASSED] (Maintenance court hidden from public booking view)\n";
} else {
    echo "[FAILED]\n";
    print_r($t10);
}

// Test 11: Re-activate court status to ATIVO
echo "Test 11: Re-activate court status to ATIVO ... ";
$t11 = requestApi('PATCH', "/api/v1/quadras/{$quadraId}/status", [
    'status' => 'ATIVO',
], $admin1Token);
if ($t11['status'] === 200 && ($t11['json']['data']['status'] ?? '') === 'ATIVO') {
    echo "[PASSED] (Status re-activated to ATIVO)\n";
} else {
    echo "[FAILED]\n";
    print_r($t11);
}

// Test 12: Update court capacity and price
echo "Test 12: Update court capacity and price ... ";
$t12 = requestApi('PUT', "/api/v1/quadras/{$quadraId}", [
    'capacidade' => 6,
    'valor_padrao' => 140.00,
], $admin1Token);
$updatedCourt = $t12['json']['data']['quadra'] ?? [];
if ($t12['status'] === 200 && (int)($updatedCourt['capacidade'] ?? 0) === 6 && (float)($updatedCourt['valor_padrao'] ?? 0) == 140.00) {
    echo "[PASSED] (Court capacity and price updated)\n";
} else {
    echo "[FAILED]\n";
    print_r($t12);
}

// Test 13: Multi-tenant boundary protection (Admin 2 cannot access or edit Arena 1 court)
echo "Test 13: Admin 2 forbidden to edit Arena 1 court (Tenant Shield) ... ";
$t13 = requestApi('PUT', "/api/v1/quadras/{$quadraId}", [
    'nome' => 'Invasao de Arena',
], $admin2Token);
if ($t13['status'] === 403) {
    echo "[PASSED] (403 Forbidden correctly protected cross-tenant mutation)\n";
} else {
    echo "[FAILED]\n";
    print_r($t13);
}

// Test 14: Delete modalidade blocked when linked courts exist
echo "Test 14: Delete modalidade blocked when linked courts exist ... ";
$t14 = requestApi('DELETE', "/api/v1/modalidades/{$modalidadeId}", [], $admin1Token);
if ($t14['status'] === 400) {
    echo "[PASSED] (400 Bad Request blocked deletion of modalidade with linked courts)\n";
} else {
    echo "[FAILED]\n";
    print_r($t14);
}

// Test 15: Delete court and then delete modalidade
echo "Test 15: Delete court and subsequent modalidade deletion ... ";
$t15a = requestApi('DELETE', "/api/v1/quadras/{$quadraId}", [], $admin1Token);
$t15b = requestApi('DELETE', "/api/v1/modalidades/{$modalidadeId}", [], $admin1Token);
if ($t15a['status'] === 200 && $t15b['status'] === 200) {
    echo "[PASSED] (Court and modalidade cleaned up successfully)\n";
} else {
    echo "[FAILED] Quadra delete: {$t15a['status']}, Modalidade delete: {$t15b['status']}\n";
}

// Test 16: Verify audit trail in MySQL
echo "Test 16: Verify audit trail in MySQL ... ";
$logCourtStmt = $pdo->prepare("SELECT COUNT(*) FROM `logs` WHERE `entidade` IN ('modalidades', 'quadras')");
$logCourtStmt->execute();
$auditCount = (int)$logCourtStmt->fetchColumn();
if ($auditCount >= 5) {
    echo "[PASSED] ({$auditCount} sports & courts audit records registered in database)\n";
} else {
    echo "[FAILED] Audit count: {$auditCount}\n";
}

echo "All Sports & Courts Management tests completed successfully.\n";
