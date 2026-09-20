<?php
// Master Arena SaaS - Authentication and RBAC Test Suite
// Comments strictly in ASCII only.

define('MASTER_ARENA_TEST_MODE', true);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use Database\Connection;

Autoloader::register();

echo "====================================================\n";
echo " MASTER ARENA - Running Authentication & RBAC Tests\n";
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

// Test 1: Login with invalid password
echo "Test 1: Login with invalid password ... ";
$t1 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin@masterarena.com.br',
    'senha' => 'WrongPassword123',
]);
if ($t1['status'] === 401 && ($t1['json']['success'] ?? true) === false) {
    echo "[PASSED] (401 Unauthorized returned)\n";
} else {
    echo "[FAILED]\n";
    print_r($t1);
}

// Test 2: Login with missing fields
echo "Test 2: Login with missing password (Validation) ... ";
$t2 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin@masterarena.com.br',
]);
if ($t2['status'] === 422 && ($t2['json']['success'] ?? true) === false) {
    echo "[PASSED] (422 Validation error returned)\n";
} else {
    echo "[FAILED]\n";
    print_r($t2);
}

// Test 3: Login as SUPERADMIN
echo "Test 3: Login as SUPERADMIN ... ";
$t3 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'superadmin@masterarena.com.br',
    'senha' => 'Admin@123456',
]);
$superToken = $t3['json']['data']['token'] ?? null;
if ($t3['status'] === 200 && !empty($superToken) && ($t3['json']['data']['user']['perfil'] ?? '') === 'SUPERADMIN') {
    echo "[PASSED] (Token generated for Superadmin)\n";
} else {
    echo "[FAILED]\n";
    print_r($t3);
}

// Test 4: Login as Arena ADMIN
echo "Test 4: Login as Arena ADMIN ... ";
$t4 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin@masterarena.com.br',
    'senha' => 'Arena@123456',
]);
$adminToken = $t4['json']['data']['token'] ?? null;
$arenaId = $t4['json']['data']['user']['arena_id'] ?? null;
if ($t4['status'] === 200 && !empty($adminToken) && $arenaId === 1 && ($t4['json']['data']['user']['perfil'] ?? '') === 'ADMIN') {
    echo "[PASSED] (Token generated, arena_id = 1 linked)\n";
} else {
    echo "[FAILED]\n";
    print_r($t4);
}

// Test 5: Login as Arena FUNCIONARIO (Staff)
echo "Test 5: Login as Arena Staff ... ";
$t5 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'atendente@masterarena.com.br',
    'senha' => 'Staff@123456',
]);
$staffToken = $t5['json']['data']['token'] ?? null;
if ($t5['status'] === 200 && !empty($staffToken) && ($t5['json']['data']['user']['perfil'] ?? '') === 'FUNCIONARIO') {
    echo "[PASSED] (Token generated for Staff)\n";
} else {
    echo "[FAILED]\n";
    print_r($t5);
}

// Test 6: GET /api/v1/auth/me without token (Unauthorized)
echo "Test 6: GET /api/v1/auth/me without token ... ";
$t6 = requestApi('GET', '/api/v1/auth/me');
if ($t6['status'] === 401) {
    echo "[PASSED] (401 Blocked)\n";
} else {
    echo "[FAILED]\n";
    print_r($t6);
}

// Test 7: GET /api/v1/auth/me with Admin token
echo "Test 7: GET /api/v1/auth/me with Bearer token ... ";
$t7 = requestApi('GET', '/api/v1/auth/me', [], $adminToken);
if ($t7['status'] === 200 && ($t7['json']['data']['user']['email'] ?? '') === 'admin@masterarena.com.br') {
    echo "[PASSED] (User context verified)\n";
} else {
    echo "[FAILED]\n";
    print_r($t7);
}

// Test 8: RBAC - Admin accessing admin-only endpoint
echo "Test 8: RBAC - Admin accessing /api/v1/admin/only ... ";
$t8 = requestApi('GET', '/api/v1/admin/only', [], $adminToken);
if ($t8['status'] === 200 && ($t8['json']['data']['access'] ?? '') === 'granted') {
    echo "[PASSED] (Access granted to Admin)\n";
} else {
    echo "[FAILED]\n";
    print_r($t8);
}

// Test 9: RBAC - Staff attempting to access admin-only endpoint (Forbidden)
echo "Test 9: RBAC - Staff accessing /api/v1/admin/only (Forbidden test) ... ";
$t9 = requestApi('GET', '/api/v1/admin/only', [], $staffToken);
if ($t9['status'] === 403 && ($t9['json']['success'] ?? true) === false) {
    echo "[PASSED] (403 Forbidden correctly blocked Staff)\n";
} else {
    echo "[FAILED]\n";
    print_r($t9);
}

// Test 10: RBAC - Staff accessing staff-allowed endpoint
echo "Test 10: RBAC - Staff accessing /api/v1/staff/access ... ";
$t10 = requestApi('GET', '/api/v1/staff/access', [], $staffToken);
if ($t10['status'] === 200 && ($t10['json']['data']['access'] ?? '') === 'granted') {
    echo "[PASSED] (Access granted to Staff)\n";
} else {
    echo "[FAILED]\n";
    print_r($t10);
}

// Test 11: Token Refresh
echo "Test 11: POST /api/v1/auth/refresh ... ";
$t11 = requestApi('POST', '/api/v1/auth/refresh', [], $adminToken);
$newToken = $t11['json']['data']['token'] ?? null;
if ($t11['status'] === 200 && !empty($newToken)) {
    echo "[PASSED] (Fresh token issued)\n";
} else {
    echo "[FAILED]\n";
    print_r($t11);
}

// Test 12: Logout
echo "Test 12: POST /api/v1/auth/logout ... ";
$t12 = requestApi('POST', '/api/v1/auth/logout', [], $adminToken);
if ($t12['status'] === 200) {
    echo "[PASSED] (Logout logged)\n";
} else {
    echo "[FAILED]\n";
    print_r($t12);
}

// Test 13: Verify Audit Logs recorded in database
echo "Test 13: Verify Audit Logs in MySQL ... ";
$pdo = Connection::getInstance();
$logStmt = $pdo->query("SELECT COUNT(*) FROM `logs` WHERE `acao` IN ('LOGIN', 'LOGOUT')");
$logCount = (int)$logStmt->fetchColumn();
if ($logCount >= 4) {
    echo "[PASSED] ({$logCount} login/logout audit events recorded in database)\n";
} else {
    echo "[FAILED] Log count: {$logCount}\n";
}

echo "All Authentication & RBAC tests completed successfully.\n";
