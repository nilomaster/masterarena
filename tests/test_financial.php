<?php
// Master Arena SaaS - Financial Engine, PIX Payments & Cash Register Test Suite
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
echo " MASTER ARENA - Financial, PIX & Cash Register Tests\n";
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
            'error' => $e->getMessage(),
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

$loginStaff1 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'atendente@masterarena.com.br',
    'senha' => 'Staff@123456',
]);
$staff1Token = $loginStaff1['json']['data']['token'] ?? null;


$loginAdmin2 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin2@masterarena.com.br',
    'senha' => 'Arena@123456',
]);
$admin2Token = $loginAdmin2['json']['data']['token'] ?? null;

if ($superToken && $admin1Token && $staff1Token && $admin2Token) {
    echo "OK (All 4 tokens acquired)\n\n";
} else {
    echo "FAILED to authenticate test users.\n";
    exit(1);
}

$arenaId = 1;

echo "--- 1. Testing PIX Engine & EMVCo Checksum Generation ---\n";

$pixService = new \App\Services\PixPayloadService();
$testCrc = $pixService->calculateCrc16('00020126360014br.gov.bcb.pix0114+5511999999999520400005303986540510.005802BR5912MASTER ARENA6009SAO PAULO62070503***6304');

assertTest(
    "Calculate valid 4-character hex CRC16-CCITT checksum",
    strlen($testCrc) === 4 && ctype_xdigit($testCrc),
    "Generated CRC: {$testCrc}"
);

$fullPix = $pixService->generatePayload('12345678000199', 150.00, 'AG123', 'Arena Beach', 'Curitiba');
assertTest(
    "Generate full EMVCo PIX Copia e Cola payload with Bacen prefix (000201)",
    str_starts_with($fullPix, '000201') && str_contains($fullPix, '12345678000199') && str_contains($fullPix, '150.00'),
    $fullPix
);

echo "\n--- 2. Testing Booking PIX Billing & Automated Reconciliation ---\n";

$targetDate = date('Y-m-d', strtotime('+10 days'));
$bookingRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/agendamentos", [
    'quadra_id' => 1,
    'data' => $targetDate,
    'hora_inicio' => '10:00',
    'hora_fim' => '11:00',
    'cliente_nome' => 'Marcos Pagador PIX',
    'cliente_telefone' => '11987654321',
]);

$bookingId = $bookingRes['json']['data']['agendamento']['id'] ?? 0;
assertTest(
    "Create booking for PIX payment test (HTTP 201)",
    $bookingRes['status'] === 201 && $bookingId > 0,
    json_encode($bookingRes['json'])
);

// 2.2 Generate PIX for this booking
$pixRes = requestApi('POST', "/api/v1/agendamentos/{$bookingId}/pix");
assertTest(
    "Generate PIX billing for booking #{$bookingId} (HTTP 201)",
    $pixRes['status'] === 201 &&
    isset($pixRes['json']['data']['pix']['copia_e_cola']) &&
    isset($pixRes['json']['data']['pagamento']['id']),
    json_encode($pixRes['json'])
);

$paymentId = $pixRes['json']['data']['pagamento']['id'] ?? 0;
$pixCode = $pixRes['json']['data']['pix']['copia_e_cola'] ?? '';
$txid = $pixRes['json']['data']['pix']['txid'] ?? '';

// 2.3 Simulate PIX Webhook notification
$webhookRes = requestApi('POST', "/api/v1/webhooks/pix", [
    'payment_id' => $paymentId,
    'txid' => $txid,
]);

assertTest(
    "Webhook PIX instant settlement (HTTP 200)",
    $webhookRes['status'] === 200 && ($webhookRes['json']['data']['status'] ?? '') === 'PAGO',
    json_encode($webhookRes['json'])
);

// 2.4 Verify booking status automatically transitioned to CONFIRMADO
$bookingAfterPay = requestApi('GET', "/api/v1/agendamentos/{$bookingId}");
assertTest(
    "Booking automatically transitioned to CONFIRMADO upon PIX settlement",
    $bookingAfterPay['status'] === 200 &&
    ($bookingAfterPay['json']['data']['agendamento']['status'] ?? '') === 'CONFIRMADO',
    json_encode($bookingAfterPay['json'])
);

// 2.5 Verify transition registered in agendamento_historico
$history = $bookingAfterPay['json']['data']['historico'] ?? [];
$historyFound = false;
foreach ($history as $h) {
    if ($h['status_novo'] === 'CONFIRMADO') {
        $historyFound = true;
        break;
    }
}
assertTest(
    "Payment confirmation audit recorded in agendamento_historico",
    $historyFound,
    json_encode($history)
);

echo "\n--- 3. Testing Direct Counter Payment & Refund ---\n";

// 3.1 Register direct counter payment (R$ 80.00 cash)
$directRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/pagamentos", [
    'valor' => 80.00,
    'tipo' => 'RECEITA',
    'categoria' => 'CONSUMO_BAR',
    'descricao' => 'Consumo de bebidas no bar',
    'metodo_pagamento' => 'DINHEIRO',
    'status' => 'PAGO',
], $staff1Token);

$directPayId = $directRes['json']['data']['pagamento']['id'] ?? 0;
assertTest(
    "Staff register direct counter payment (R$ 80,00 DINHEIRO) (HTTP 201)",
    $directRes['status'] === 201 && $directPayId > 0,
    json_encode($directRes['json'])
);

// 3.2 Refund transaction
$refundRes = requestApi('POST', "/api/v1/pagamentos/{$directPayId}/estornar", [
    'motivo' => 'Cliente cancelou pedido do bar',
], $admin1Token);

assertTest(
    "Admin refund payment (HTTP 200)",
    $refundRes['status'] === 200 &&
    ($refundRes['json']['data']['pagamento']['status'] ?? '') === 'ESTORNADO',
    json_encode($refundRes['json'])
);

echo "\n--- 4. Testing Cash Register (Caixa Balcao) Operations ---\n";

// Ensure no open session left from previous runs
$statusInit = requestApi('GET', "/api/v1/arenas/{$arenaId}/caixa/status", [], $staff1Token);
if (!empty($statusInit['json']['data']['caixa']['caixa_aberto'])) {
    $openSessId = (int)$statusInit['json']['data']['caixa']['sessao']['id'];
    requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/fechar", [
        'caixa_sessao_id' => $openSessId,
        'saldo_informado' => 0.00,
    ], $staff1Token);
}

// 4.1 Open cash register shift with R$ 100.00 float
$openRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/abrir", [
    'saldo_inicial' => 100.00,
    'observacoes' => 'Turno da manha teste',
], $staff1Token);

$sessionId = $openRes['json']['data']['sessao']['id'] ?? 0;
assertTest(
    "Staff open cash register shift (Float: R$ 100,00) (HTTP 201)",
    $openRes['status'] === 201 && $sessionId > 0,
    json_encode($openRes['json'])
);

// 4.2 Prevent duplicate open cash register shift in the same arena
$dupOpen = requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/abrir", [
    'saldo_inicial' => 50.00,
], $staff1Token);

assertTest(
    "Prevent duplicate open shift in the same arena (HTTP 409 Conflict)",
    $dupOpen['status'] === 409,
    json_encode($dupOpen['json'])
);

// 4.3 Cash Inflow movement (Suprimento: R$ 50.00)
$supRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/movimentacao", [
    'tipo' => 'SUPRIMENTO',
    'valor' => 50.00,
    'motivo' => 'Reforco de troco em moedas',
], $staff1Token);

assertTest(
    "Register cash suprimento (R$ 50,00) (HTTP 201)",
    $supRes['status'] === 201 &&
    (float)($supRes['json']['data']['balanco']['saldo_dinheiro_esperado'] ?? 0) === 150.00,
    json_encode($supRes['json'])
);

// 4.4 Direct cash sale during shift (R$ 70.00)
$saleRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/pagamentos", [
    'valor' => 70.00,
    'tipo' => 'RECEITA',
    'categoria' => 'ALUGUEL_RAQUETE',
    'descricao' => 'Aluguel de 2 raquetes',
    'metodo_pagamento' => 'DINHEIRO',
    'status' => 'PAGO',
], $staff1Token);

assertTest(
    "Cash sale registered and attached to open cash register session (R$ 70,00)",
    $saleRes['status'] === 201,
    json_encode($saleRes['json'])
);

// 4.5 Cash Outflow movement (Sangria: R$ 60.00)
$sangriaRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/movimentacao", [
    'tipo' => 'SANGRIA',
    'valor' => 60.00,
    'motivo' => 'Sangria para cofre seguro',
], $staff1Token);

// Current cash should be: 100 (float) + 50 (sup) + 70 (sale) - 60 (sangria) = 160.00
assertTest(
    "Register cash sangria (R$ 60,00) and verify drawer balance (Expected: R$ 160,00)",
    $sangriaRes['status'] === 201 &&
    (float)($sangriaRes['json']['data']['balanco']['saldo_dinheiro_esperado'] ?? 0) === 160.00,
    json_encode($sangriaRes['json'])
);

// 4.6 Attempt excessive sangria exceeding drawer balance
$excessSangria = requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/movimentacao", [
    'tipo' => 'SANGRIA',
    'valor' => 999.00,
    'motivo' => 'Tentativa de retirada acima do saldo',
], $staff1Token);

assertTest(
    "Block excessive sangria exceeding drawer cash balance (HTTP 422)",
    $excessSangria['status'] === 422,
    json_encode($excessSangria['json'])
);

// 4.7 Close cash register shift with exact blind count
$closeRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/fechar", [
    'caixa_sessao_id' => $sessionId,
    'saldo_informado' => 160.00,
    'observacoes' => 'Conferencia exata no final do turno',
], $staff1Token);

assertTest(
    "Close cash register shift with exact count (Diferenca: 0.00) (HTTP 200)",
    $closeRes['status'] === 200 &&
    (float)($closeRes['json']['data']['diferenca'] ?? -1) === 0.00 &&
    ($closeRes['json']['data']['status_diferenca'] ?? '') === 'EXATO',
    json_encode($closeRes['json'])
);

echo "\n--- 5. Testing Multi-Tenant Security Shield ---\n";

// Admin 2 (Arena 7) forbidden to access Arena 1 financial data
$tenantShieldRes = requestApi('GET', "/api/v1/arenas/{$arenaId}/pagamentos", [], $admin2Token);
assertTest(
    "Multi-Tenant Shield: Admin 2 forbidden to access Arena 1 payments (HTTP 403)",
    $tenantShieldRes['status'] === 403,
    json_encode($tenantShieldRes['json'])
);

$tenantCaixaShield = requestApi('POST', "/api/v1/arenas/{$arenaId}/caixa/abrir", [
    'saldo_inicial' => 100.00,
], $admin2Token);
assertTest(
    "Multi-Tenant Shield: Admin 2 forbidden to open Arena 1 cash register (HTTP 403)",
    $tenantCaixaShield['status'] === 403,
    json_encode($tenantCaixaShield['json'])
);

echo "\n--- 6. Testing Financial Executive Summary Endpoint ---\n";

$summaryRes = requestApi('GET', "/api/v1/arenas/{$arenaId}/financeiro/resumo", [], $admin1Token);
assertTest(
    "Get financial executive summary metrics for Arena 1 (HTTP 200)",
    $summaryRes['status'] === 200 &&
    isset($summaryRes['json']['data']['resumo']['faturamento_bruto']) &&
    isset($summaryRes['json']['data']['resumo']['metodos']),
    json_encode($summaryRes['json'])
);

echo "\n====================================================\n";
echo " FINANCIAL TEST SUMMARY\n";
echo " Passed: {$passedTests}\n";
echo " Failed: {$failedTests}\n";
echo "====================================================\n";

if ($failedTests > 0) {
    exit(1);
}
exit(0);
