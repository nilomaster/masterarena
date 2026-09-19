<?php
// Master Arena SaaS - API Core Unit & Route Test Suite
// Comments strictly in ASCII only.

define('MASTER_ARENA_TEST_MODE', true);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

Autoloader::register();

echo "====================================================\n";
echo " MASTER ARENA - Running API Core Route Tests\n";
echo "====================================================\n";

function simulateRequest(string $method, string $uri, array $body = [], array $headers = []): array
{
    // Setup environment
    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_SERVER['REQUEST_URI'] = $uri;
    $_GET = [];
    $_POST = [];

    $parsed = parse_url($uri);
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $_GET);
    }

    if (strtoupper($method) === 'POST' || strtoupper($method) === 'PUT') {
        $_POST = $body;
    }

    $request = new Request();
    $router = new Router();

    // Register root
    $router->get('/', function (Request $req) {
        Response::success(['app' => 'MASTER ARENA SaaS'], 'Sistema operacional');
    });

    // Register API routes
    require __DIR__ . '/../routes/api.php';

    ob_start();
    try {
        $router->dispatch($request);
    } catch (Throwable $e) {
        return ['status' => 500, 'error' => $e->getMessage()];
    }
    $output = ob_get_clean();

    $code = http_response_code();
    $json = json_decode($output, true);

    return [
        'status' => $code,
        'json' => $json,
        'raw' => $output,
    ];
}

// Test 1: GET /
echo "Test 1: GET / ... ";
$res1 = simulateRequest('GET', '/');
if ($res1['status'] === 200 && ($res1['json']['success'] ?? false) === true) {
    echo "[PASSED]\n";
} else {
    echo "[FAILED] Status: {$res1['status']}\n";
    print_r($res1);
}

// Test 2: GET /api/v1
echo "Test 2: GET /api/v1 ... ";
$res2 = simulateRequest('GET', '/api/v1');
if ($res2['status'] === 200 && ($res2['json']['data']['api_version'] ?? '') === 'v1') {
    echo "[PASSED]\n";
} else {
    echo "[FAILED] Status: {$res2['status']}\n";
    print_r($res2);
}

// Test 3: GET /api/v1/health
echo "Test 3: GET /api/v1/health ... ";
$res3 = simulateRequest('GET', '/api/v1/health');
if ($res3['status'] === 200 && ($res3['json']['data']['status'] ?? '') === 'healthy') {
    echo "[PASSED] (Database connected)\n";
} else {
    echo "[FAILED] Status: {$res3['status']}\n";
    print_r($res3);
}

// Test 4: GET /api/v1/ping
echo "Test 4: GET /api/v1/ping ... ";
$res4 = simulateRequest('GET', '/api/v1/ping');
if ($res4['status'] === 200 && ($res4['json']['data']['pong'] ?? false) === true) {
    echo "[PASSED]\n";
} else {
    echo "[FAILED] Status: {$res4['status']}\n";
    print_r($res4);
}

// Test 5: GET /api/v1/version
echo "Test 5: GET /api/v1/version ... ";
$res5 = simulateRequest('GET', '/api/v1/version');
if ($res5['status'] === 200 && ($res5['json']['data']['api_version'] ?? '') === 'v1.0.0') {
    echo "[PASSED]\n";
} else {
    echo "[FAILED] Status: {$res5['status']}\n";
    print_r($res5);
}

// Test 6: GET /api/v1/rota-inexistente (404 Not Found)
echo "Test 6: GET /api/v1/rota-inexistente (404 Test) ... ";
$res6 = simulateRequest('GET', '/api/v1/rota-inexistente');
if ($res6['status'] === 404 && ($res6['json']['success'] ?? true) === false) {
    echo "[PASSED] (Correct 404 returned)\n";
} else {
    echo "[FAILED] Status: {$res6['status']}\n";
    print_r($res6);
}

// Test 7: POST /api/v1/health (405 Method Not Allowed)
echo "Test 7: POST /api/v1/health (405 Test) ... ";
$res7 = simulateRequest('POST', '/api/v1/health');
if ($res7['status'] === 405 && ($res7['json']['success'] ?? true) === false) {
    echo "[PASSED] (Correct 405 returned)\n";
} else {
    echo "[FAILED] Status: {$res7['status']}\n";
    print_r($res7);
}

echo "All tests finished.\n";
