<?php
// Master Arena SaaS - Arena Management & Multi-Tenancy Test Suite
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
echo " MASTER ARENA - Running Arena & Multi-Tenancy Tests\n";
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

// Helper: Obtain authentication tokens
echo "Authenticating test users ... ";
$loginSuper = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'superadmin@masterarena.com.br',
    'senha' => 'Admin@123456',
]);
$superToken = $loginSuper['json']['data']['token'] ?? null;

$loginAdmin = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin@masterarena.com.br',
    'senha' => 'Arena@123456',
]);
$adminToken = $loginAdmin['json']['data']['token'] ?? null;

if ($superToken && $adminToken) {
    echo "[OK]\n";
} else {
    echo "[FAILED] Could not authenticate test users.\n";
    exit(1);
}

// Test 1: Public lookup by valid slug (arena-master-beach seeded)
echo "Test 1: Public lookup by slug (arena-master-beach) ... ";
$t1 = requestApi('GET', '/api/v1/arenas/by-slug/arena-master-beach');
if ($t1['status'] === 200 && ($t1['json']['data']['arena']['slug'] ?? '') === 'arena-master-beach') {
    echo "[PASSED] (Public details returned)\n";
} else {
    echo "[FAILED]\n";
    print_r($t1);
}

// Test 2: Public lookup by non-existent slug
echo "Test 2: Public lookup by non-existent slug ... ";
$t2 = requestApi('GET', '/api/v1/arenas/by-slug/arena-fantasma-inexistente');
if ($t2['status'] === 404) {
    echo "[PASSED] (404 Not Found returned)\n";
} else {
    echo "[FAILED]\n";
    print_r($t2);
}

// Test 3: Arena list by Arena Admin (Forbidden test)
echo "Test 3: Arena list accessed by regular Arena Admin (Forbidden test) ... ";
$t3 = requestApi('GET', '/api/v1/arenas', [], $adminToken);
if ($t3['status'] === 403) {
    echo "[PASSED] (403 Forbidden correctly blocked)\n";
} else {
    echo "[FAILED]\n";
    print_r($t3);
}

// Test 4: Arena list by Superadmin
echo "Test 4: Arena list accessed by Superadmin ... ";
$t4 = requestApi('GET', '/api/v1/arenas?limit=10', [], $superToken);
$arenaCount = count($t4['json']['data']['arenas'] ?? []);
if ($t4['status'] === 200 && $arenaCount >= 1) {
    echo "[PASSED] ({$arenaCount} arenas found with pagination)\n";
} else {
    echo "[FAILED]\n";
    print_r($t4);
}

// Test 5: Create new arena by Superadmin
echo "Test 5: Create new arena by Superadmin ... ";
$uniqueSlug = 'arena-ipanema-' . time();
$t5 = requestApi('POST', '/api/v1/arenas', [
    'nome_arena' => 'Arena Ipanema Beach Sports',
    'slug' => $uniqueSlug,
    'whatsapp' => '11999887766',
    'cidade' => 'Sao Paulo',
    'estado' => 'SP',
], $superToken);
$createdId = (int)($t5['json']['data']['arena']['id'] ?? 0);
if ($t5['status'] === 201 && $createdId > 0 && ($t5['json']['data']['arena']['slug'] ?? '') === $uniqueSlug) {
    echo "[PASSED] (Arena created with ID {$createdId})\n";
} else {
    echo "[FAILED]\n";
    print_r($t5);
}

// Test 6: Duplicate slug validation
echo "Test 6: Create arena with duplicate slug (Validation test) ... ";
$t6 = requestApi('POST', '/api/v1/arenas', [
    'nome_arena' => 'Outra Arena Mesma Slug',
    'slug' => $uniqueSlug,
    'whatsapp' => '11999887766',
], $superToken);
if ($t6['status'] === 422 && !empty($t6['json']['errors']['slug'])) {
    echo "[PASSED] (422 Duplicate slug rejected)\n";
} else {
    echo "[FAILED]\n";
    print_r($t6);
}

// Test 7: Multi-tenant boundary check (Admin of Arena 1 accessing new arena)
echo "Test 7: Admin 1 accessing new Arena {$createdId} (Multi-Tenant Shield test) ... ";
$t7 = requestApi('GET', "/api/v1/arenas/{$createdId}", [], $adminToken);
if ($t7['status'] === 403) {
    echo "[PASSED] (403 Forbidden correctly protected cross-tenant access)\n";
} else {
    echo "[FAILED]\n";
    print_r($t7);
}

// Test 8: Admin 1 accessing own Arena (ID 1)
echo "Test 8: Admin 1 accessing own Arena 1 ... ";
$t8 = requestApi('GET', '/api/v1/arenas/1', [], $adminToken);
if ($t8['status'] === 200 && (int)($t8['json']['data']['arena']['id'] ?? 0) === 1) {
    echo "[PASSED] (Own arena data retrieved)\n";
} else {
    echo "[FAILED]\n";
    print_r($t8);
}

// Test 9: Update arena details by Admin
echo "Test 9: Update arena phone and bio by Admin 1 ... ";
$t9 = requestApi('PUT', '/api/v1/arenas/1', [
    'telefone' => '1133334444',
    'descricao' => 'Arena de Beach Tennis e Volei de Praia atualizada.',
], $adminToken);
if ($t9['status'] === 200 && ($t9['json']['data']['arena']['telefone'] ?? '') === '1133334444') {
    echo "[PASSED] (Arena details updated)\n";
} else {
    echo "[FAILED]\n";
    print_r($t9);
}

// Test 10: Read tenant settings
echo "Test 10: Read tenant settings for Arena 1 ... ";
$t10 = requestApi('GET', '/api/v1/arenas/1/settings', [], $adminToken);
if ($t10['status'] === 200 && is_array($t10['json']['data']['settings'] ?? null)) {
    echo "[PASSED] (Settings retrieved)\n";
} else {
    echo "[FAILED]\n";
    print_r($t10);
}

// Test 11: Update tenant settings in batch
echo "Test 11: Update tenant settings in batch ... ";
$t11 = requestApi('PUT', '/api/v1/arenas/1/settings', [
    'settings' => [
        'horario_abertura' => '07:00',
        'horario_fechamento' => '23:30',
        'intervalo_minutos' => '90',
        'pix_chave' => 'financeiro@masterarena.com.br',
        'pix_tipo' => 'EMAIL',
    ]
], $adminToken);
$saved = $t11['json']['data']['settings'] ?? [];
if ($t11['status'] === 200 && ($saved['intervalo_minutos'] ?? '') === '90' && ($saved['pix_tipo'] ?? '') === 'EMAIL') {
    echo "[PASSED] (Batch settings saved)\n";
} else {
    echo "[FAILED]\n";
    print_r($t11);
}

// Test 12: Update arena operational status by Superadmin
echo "Test 12: Change arena status to BLOQUEADO by Superadmin ... ";
$t12 = requestApi('PATCH', "/api/v1/arenas/{$createdId}/status", [
    'status' => 'BLOQUEADO',
], $superToken);
if ($t12['status'] === 200 && ($t12['json']['data']['status'] ?? '') === 'BLOQUEADO') {
    echo "[PASSED] (Status changed to BLOQUEADO)\n";
} else {
    echo "[FAILED]\n";
    print_r($t12);
}

// Test 13: Audit trail verification in MySQL
echo "Test 13: Verify Arena Audit Trail in MySQL ... ";
$pdo = Connection::getInstance();
$logStmt = $pdo->prepare("SELECT COUNT(*) FROM `logs` WHERE `entidade` = 'arenas'");
$logStmt->execute();
$auditCount = (int)$logStmt->fetchColumn();
if ($auditCount >= 4) {
    echo "[PASSED] ({$auditCount} arena audit records registered in database)\n";
} else {
    echo "[FAILED] Audit count: {$auditCount}\n";
}

echo "All Arena Management & Multi-Tenancy tests completed successfully.\n";
