<?php
// Master Arena SaaS - Bookings, Double-Booking Prevention & Coupons Test Suite
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
echo " MASTER ARENA - Bookings Engine & Coupons Tests\n";
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
        return [
            'status' => 500,
            'json' => ['success' => false, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()],
            'error' => $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine(),
            'raw' => $e->getMessage(),
        ];
    }
    $raw = ob_get_clean();

    if (empty($json) && !empty($raw)) {
        $json = json_decode($raw, true) ?: [];
    }

    return [
        'status' => $statusCode,
        'json' => $json ?: [],
        'raw' => $raw,
    ];
}


$passedTests = 0;
$failedTests = 0;

function assertTest(string $description, bool $condition, ?string $detail = null): void
{
    global $passedTests, $failedTests;
    if ($condition) {
        echo "[PASS] " . $description . "\n";
        $passedTests++;
    } else {
        echo "[FAIL] " . $description;
        if ($detail) {
            echo " -> " . $detail;
        }
        echo "\n";
        $failedTests++;
    }
}

// 0. Authenticate Superadmin
echo "Authenticating Superadmin ... ";
$loginResp = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'superadmin@masterarena.com.br',
    'senha' => 'Admin@123456',
]);

$superToken = $loginResp['json']['data']['token'] ?? null;
if (!$superToken) {
    echo "FAILED to authenticate superadmin. Aborting.\n";
    echo "Response: " . json_encode($loginResp) . "\n";
    exit(1);
}
echo "OK (Token acquired)\n\n";


$arenaId = 1;

// Ensure court exists
$courtResp = requestApi('GET', "/api/v1/arenas/{$arenaId}/quadras", [], $superToken);
$quadras = $courtResp['json']['data']['quadras'] ?? [];
$targetCourt = null;
foreach ($quadras as $q) {
    if ($q['status'] === 'ATIVO') {
        $targetCourt = $q;
        break;
    }
}

if (!$targetCourt) {
    echo "No active court found. Creating one for test...\n";
    $modResp = requestApi('GET', "/api/v1/arenas/{$arenaId}/modalidades", [], $superToken);
    $mods = $modResp['json']['data']['modalidades'] ?? [];
    $modId = !empty($mods) ? $mods[0]['id'] : 1;

    $createCourt = requestApi('POST', "/api/v1/arenas/{$arenaId}/quadras", [
        'modalidade_id' => $modId,
        'nome' => 'Quadra Booking Test',
        'capacidade' => 4,
        'valor_padrao' => 100.00,
        'status' => 'ATIVO',
    ], $superToken);
    $targetCourt = $createCourt['json']['data']['quadra'] ?? null;
}

$courtId = (int)$targetCourt['id'];
$testDate = date('Y-m-d', strtotime('+3 days')); // 3 days ahead to ensure clean slot

echo "--- 1. Testing Coupons (Cupons de Desconto) ---\n";

// 1.1 Create percentage coupon (20% OFF)
$codePercent = 'TEST20_' . substr(md5(uniqid()), 0, 5);
$resCupom1 = requestApi('POST', "/api/v1/arenas/{$arenaId}/cupons", [
    'codigo' => $codePercent,
    'descricao' => 'Cupom 20% desconto para testes',
    'tipo' => 'PERCENTUAL',
    'valor' => 20,
    'data_inicio' => date('Y-m-d', strtotime('-1 day')),
    'data_fim' => date('Y-m-d', strtotime('+10 days')),
    'limite_uso' => 100,
    'limite_por_cliente' => 2,
    'ativo' => true,
], $superToken);

assertTest(
    "Create 20% discount coupon ($codePercent)",
    $resCupom1['status'] === 201 && isset($resCupom1['json']['data']['cupom']['id']),
    json_encode($resCupom1['json'])
);
$cupomPercentId = $resCupom1['json']['data']['cupom']['id'] ?? 0;

// 1.2 Validate percentage coupon simulation
$valResp1 = requestApi('POST', "/api/v1/arenas/{$arenaId}/cupons/validar", [
    'codigo' => $codePercent,
    'preco_original' => 100.00,
]);
assertTest(
    "Validate 20% coupon calculation on R$ 100,00 (expected discount 20.00, final 80.00)",
    $valResp1['status'] === 200 &&
    (float)($valResp1['json']['data']['desconto'] ?? 0) === 20.00 &&
    (float)($valResp1['json']['data']['valor_final'] ?? 0) === 80.00,
    json_encode($valResp1['json'])
);

// 1.3 Create fixed value coupon (R$ 35,00 OFF)
$codeFixed = 'FIXO35_' . substr(md5(uniqid()), 0, 5);
$resCupom2 = requestApi('POST', "/api/v1/arenas/{$arenaId}/cupons", [
    'codigo' => $codeFixed,
    'descricao' => 'Cupom R$ 35 fixo',
    'tipo' => 'VALOR_FIXO',
    'valor' => 35.00,
    'data_inicio' => date('Y-m-d', strtotime('-1 day')),
    'data_fim' => date('Y-m-d', strtotime('+10 days')),
    'limite_uso' => 50,
    'limite_por_cliente' => 1,
    'ativo' => true,
], $superToken);

assertTest(
    "Create fixed R$ 35 discount coupon ($codeFixed)",
    $resCupom2['status'] === 201 && isset($resCupom2['json']['data']['cupom']['id']),
    json_encode($resCupom2['json'])
);

// 1.4 Validate fixed coupon simulation
$valResp2 = requestApi('POST', "/api/v1/arenas/{$arenaId}/cupons/validar", [
    'codigo' => $codeFixed,
    'preco_original' => 120.00,
]);
assertTest(
    "Validate R$ 35 fixed coupon calculation on R$ 120,00 (expected discount 35.00, final 85.00)",
    $valResp2['status'] === 200 &&
    (float)($valResp2['json']['data']['desconto'] ?? 0) === 35.00 &&
    (float)($valResp2['json']['data']['valor_final'] ?? 0) === 85.00,
    json_encode($valResp2['json'])
);

// 1.5 Validate non-existent coupon
$valInvalid = requestApi('POST', "/api/v1/arenas/{$arenaId}/cupons/validar", [
    'codigo' => 'CUPOM_NAO_EXISTE_999',
    'preco_original' => 100.00,
]);
assertTest(
    "Validate rejection of non-existent coupon (HTTP 400)",
    $valInvalid['status'] === 400,
    json_encode($valInvalid['json'])
);

echo "\n--- 2. Testing Booking Creation & Automatic Customer Onboarding ---\n";

// 2.1 Create booking with new customer and discount coupon
$clientPhone = '119' . rand(10000000, 99999999);
$bookingData = [
    'quadra_id' => $courtId,
    'data' => $testDate,
    'hora_inicio' => '10:00',
    'hora_fim' => '11:00',
    'cliente_nome' => 'Carlos Cliente Teste',
    'cliente_telefone' => $clientPhone,
    'cliente_email' => 'carlos.teste@exemplo.com.br',
    'cupom_codigo' => $codePercent,
    'observacoes' => 'Reserva de teste automatizada',
];

$resBooking1 = requestApi('POST', "/api/v1/arenas/{$arenaId}/agendamentos", $bookingData);

assertTest(
    "Create valid booking with coupon and auto-registered client (HTTP 201)",
    $resBooking1['status'] === 201 && isset($resBooking1['json']['data']['agendamento']['id']),
    json_encode($resBooking1['json'])
);

$booking1Id = $resBooking1['json']['data']['agendamento']['id'] ?? 0;
$b1 = $resBooking1['json']['data']['agendamento'] ?? [];

assertTest(
    "Verify discount applied correctly in booking record",
    isset($b1['desconto']) && (float)$b1['desconto'] > 0 && (float)$b1['valor_final'] < (float)$b1['valor_original'],
    "Original: " . ($b1['valor_original'] ?? 'null') . " | Desconto: " . ($b1['desconto'] ?? 'null') . " | Final: " . ($b1['valor_final'] ?? 'null')
);


echo "\n--- 3. Testing Double-Booking & Overlap Conflict Prevention ---\n";

// 3.1 Exact time collision (same court, date, 10:00 - 11:00)
$collisionData1 = [
    'quadra_id' => $courtId,
    'data' => $testDate,
    'hora_inicio' => '10:00',
    'hora_fim' => '11:00',
    'cliente_nome' => 'Outro Cliente Invasor',
    'cliente_telefone' => '11999990000',
];
$resCollision1 = requestApi('POST', "/api/v1/arenas/{$arenaId}/agendamentos", $collisionData1);

assertTest(
    "Prevent exact double-booking collision (HTTP 409 Conflict)",
    $resCollision1['status'] === 409,
    "Status: {$resCollision1['status']} - " . json_encode($resCollision1['json'])
);

// 3.2 Partial overlap collision (10:30 - 11:30)
$collisionData2 = [
    'quadra_id' => $courtId,
    'data' => $testDate,
    'hora_inicio' => '10:30',
    'hora_fim' => '11:30',
    'cliente_nome' => 'Terceiro Cliente',
    'cliente_telefone' => '11999990001',
];
$resCollision2 = requestApi('POST', "/api/v1/arenas/{$arenaId}/agendamentos", $collisionData2);

assertTest(
    "Prevent partial overlap booking collision (10:30 - 11:30) (HTTP 409 Conflict)",
    $resCollision2['status'] === 409,
    "Status: {$resCollision2['status']} - " . json_encode($resCollision2['json'])
);

echo "\n--- 4. Testing Operational Block Collision Prevention ---\n";

// 4.1 Create court block from 14:00 to 16:00
$resBlock = requestApi('POST', "/api/v1/arenas/{$arenaId}/bloqueios", [
    'quadra_id' => $courtId,
    'data_inicio' => $testDate,
    'data_fim' => $testDate,
    'hora_inicio' => '14:00',
    'hora_fim' => '16:00',
    'motivo' => 'Manutencao da rede e iluminacao',
], $superToken);

$blockId = $resBlock['json']['data']['bloqueio']['id'] ?? 0;
assertTest(
    "Create operational block (14:00 - 16:00)",
    $resBlock['status'] === 201 && $blockId > 0,
    json_encode($resBlock['json'])
);

// 4.2 Attempt to book during block (14:00 - 15:00)
$blockCollision = [
    'quadra_id' => $courtId,
    'data' => $testDate,
    'hora_inicio' => '14:00',
    'hora_fim' => '15:00',
    'cliente_nome' => 'Cliente Tentando em Bloqueio',
    'cliente_telefone' => '11999990002',
];
$resBlockCollision = requestApi('POST', "/api/v1/arenas/{$arenaId}/agendamentos", $blockCollision);

assertTest(
    "Prevent booking during active operational block (HTTP 409 Conflict)",
    $resBlockCollision['status'] === 409,
    "Status: {$resBlockCollision['status']} - " . json_encode($resBlockCollision['json'])
);

echo "\n--- 5. Testing Availability Grid Synchronization ---\n";

// 5.1 Fetch availability grid and check slot status
$resGrid = requestApi('GET', "/api/v1/arenas/{$arenaId}/grade?data={$testDate}");
$gridData = $resGrid['json']['data']['grade']['quadras'] ?? $resGrid['json']['data']['quadras'] ?? [];

$courtFoundInGrid = null;
foreach ($gridData as $cg) {
    $cgId = (int)($cg['id'] ?? $cg['quadra_id'] ?? 0);
    if ($cgId === $courtId) {
        $courtFoundInGrid = $cg;
        break;
    }
}

assertTest(
    "Find court in availability grid response",
    $courtFoundInGrid !== null,
    "Target court ID: {$courtId}"
);

$slot10 = null;
$slot14 = null;
if ($courtFoundInGrid) {
    foreach ($courtFoundInGrid['slots'] as $s) {
        if ($s['hora_inicio'] === '10:00:00' || $s['hora_inicio'] === '10:00') {
            $slot10 = $s;
        }
        if ($s['hora_inicio'] === '14:00:00' || $s['hora_inicio'] === '14:00') {
            $slot14 = $s;
        }
    }
}

assertTest(
    "Slot 10:00 marked as RESERVADO in grid with correct agendamento_id",
    $slot10 !== null && $slot10['status'] === 'RESERVADO' && (int)($slot10['agendamento_id'] ?? 0) === $booking1Id,
    json_encode($slot10)
);

assertTest(
    "Slot 14:00 marked as BLOQUEADO in grid due to maintenance",
    $slot14 !== null && $slot14['status'] === 'BLOQUEADO',
    json_encode($slot14)
);

echo "\n--- 6. Testing Booking Details, History & Status Update ---\n";

// 6.1 View booking details
$resShow = requestApi('GET', "/api/v1/agendamentos/{$booking1Id}");
assertTest(
    "Get booking details with client and history (HTTP 200)",
    $resShow['status'] === 200 &&
    isset($resShow['json']['data']['agendamento']['cliente_nome']) &&
    !empty($resShow['json']['data']['historico']),
    json_encode($resShow['json'])
);

// 6.2 Admin updates status to CONFIRMADO
$resStatus = requestApi('PATCH', "/api/v1/agendamentos/{$booking1Id}/status", [
    'status' => 'CONFIRMADO',
    'motivo' => 'Pagamento do sinal confirmado pelo gestor',
], $superToken);

assertTest(
    "Admin update booking status to CONFIRMADO (HTTP 200)",
    $resStatus['status'] === 200 && ($resStatus['json']['data']['agendamento']['status'] ?? '') === 'CONFIRMADO',
    json_encode($resStatus['json'])
);

echo "\n--- 7. Testing Booking Cancellation & Slot Release ---\n";

// 7.1 Cancel booking with reason
$resCancel = requestApi('POST', "/api/v1/agendamentos/{$booking1Id}/cancelar", [
    'motivo' => 'Cliente desmarcou devido a chuva intensa',
]);

assertTest(
    "Cancel booking with reason recorded (HTTP 200)",
    $resCancel['status'] === 200 && ($resCancel['json']['data']['agendamento']['status'] ?? '') === 'CANCELADO',
    json_encode($resCancel['json'])
);

// 7.2 Verify cancellation recorded in history
$resShowAfterCancel = requestApi('GET', "/api/v1/agendamentos/{$booking1Id}");
$hist = $resShowAfterCancel['json']['data']['historico'] ?? [];
$cancelEntryFound = false;
foreach ($hist as $h) {
    if ($h['status_novo'] === 'CANCELADO') {
        $cancelEntryFound = true;
        break;
    }
}
assertTest(
    "Cancellation reason recorded in immutable agendamento_historico",
    $cancelEntryFound,
    json_encode($hist)
);

// 7.3 Verify slot 10:00 is released and available again
$resGridAfterCancel = requestApi('GET', "/api/v1/arenas/{$arenaId}/grade?data={$testDate}");
$gridCourtsAfter = $resGridAfterCancel['json']['data']['grade']['quadras'] ?? $resGridAfterCancel['json']['data']['quadras'] ?? [];
$slot10After = null;

foreach ($gridCourtsAfter as $cg) {
    $cgId = (int)($cg['id'] ?? $cg['quadra_id'] ?? 0);
    if ($cgId === $courtId) {
        foreach ($cg['slots'] as $s) {
            if ($s['hora_inicio'] === '10:00:00' || $s['hora_inicio'] === '10:00') {
                $slot10After = $s;
                break;
            }
        }
    }
}
assertTest(
    "Slot 10:00 released back to LIVRE after cancellation",
    $slot10After !== null && in_array($slot10After['status'], ['LIVRE', 'DISPONIVEL'], true),
    json_encode($slot10After)
);


echo "\n--- 8. Testing Real-Time Dashboard Stats Endpoint ---\n";

$resStats = requestApi('GET', "/api/v1/arenas/{$arenaId}/agendamentos/stats", [], $superToken);
assertTest(
    "Get arena booking statistics for dashboard (HTTP 200)",
    $resStats['status'] === 200 &&
    isset($resStats['json']['data']['reservas_hoje']) &&
    isset($resStats['json']['data']['total_reservas']),
    json_encode($resStats['json'])
);

// Clean up test block
if ($blockId > 0) {
    requestApi('DELETE', "/api/v1/bloqueios/{$blockId}", [], $superToken);
}

echo "\n====================================================\n";
echo " TEST SUMMARY\n";
echo " Passed: {$passedTests}\n";
echo " Failed: {$failedTests}\n";
echo "====================================================\n";

if ($failedTests > 0) {
    exit(1);
}
exit(0);
