<?php
// Master Arena SaaS - Customer Portal, Totem & Checkin Test Suite
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
echo " MASTER ARENA - Portal, Totem & Checkin Engine Tests\n";
echo "====================================================\n";

function requestApi(string $method, string $uri, array $body = [], ?string $token = null): array
{
    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'MasterArena-KioskClient/1.0';

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
        $statusCode = 500;
        $json = [
            'success' => false,
            'message' => $e->getMessage(),
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

$pdo = Connection::getInstance();
$arenaId = 1;

// --- 1. Test Portal Info Consolidated Public Endpoint ---
echo "\n--- 1. Testing Portal Info Public Endpoint ---\n";

$portalInfoRes = requestApi('GET', "/api/v1/arenas/slug/arena-master-beach/portal-info");
assertTest(
    "Get consolidated public portal info by slug (HTTP 200)",
    $portalInfoRes['status'] === 200 &&
    isset($portalInfoRes['json']['data']['arena']) &&
    isset($portalInfoRes['json']['data']['modalidades']) &&
    isset($portalInfoRes['json']['data']['quadras']),
    json_encode($portalInfoRes['json'])
);

assertTest(
    "Portal info contains active sports and courts for arena",
    count($portalInfoRes['json']['data']['modalidades'] ?? []) > 0 &&
    count($portalInfoRes['json']['data']['quadras'] ?? []) > 0,
    "Modalidades: " . count($portalInfoRes['json']['data']['modalidades'] ?? [])
);

// --- 2. Test Booking Creation with Auto-Generated Checkin Code ---
echo "\n--- 2. Testing Booking Creation & Checkin Code Generation ---\n";

$today = date('Y-m-d');
$gradeRes = requestApi('GET', "/api/v1/arenas/{$arenaId}/grade?data={$today}");
$firstFreeSlot = null;
$gridData = $gradeRes['json']['data']['grade'] ?? [];
$quadrasGrade = $gridData['quadras'] ?? (is_array($gridData) ? $gridData : []);
$targetCourtId = 1;

foreach ($quadrasGrade as $cq) {
    $slots = $cq['slots'] ?? $cq['horarios'] ?? [];
    foreach ($slots as $sl) {
        if ($sl['status'] === 'LIVRE') {
            $firstFreeSlot = $sl;
            $targetCourtId = (int)($cq['id'] ?? $cq['quadra_id'] ?? 1);
            break 2;
        }
    }
}

$testHourStart = $firstFreeSlot ? $firstFreeSlot['hora_inicio'] : '06:00';
$testHourEnd = $firstFreeSlot ? $firstFreeSlot['hora_fim'] : '07:00';

$createBookingRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/agendamentos", [
    'quadra_id' => $targetCourtId,
    'data' => $today,
    'hora_inicio' => $testHourStart,
    'hora_fim' => $testHourEnd,
    'cliente_nome' => 'Carlos Checkin Teste',
    'cliente_telefone' => '11988887777',
]);

$booking = $createBookingRes['json']['data']['agendamento'] ?? [];
$bookingId = (int)($booking['id'] ?? 0);
$checkinCode = $booking['codigo_checkin'] ?? '';

assertTest(
    "Public booking created for today (HTTP 201)",
    $createBookingRes['status'] === 201 && $bookingId > 0,
    json_encode($createBookingRes['json'])
);

assertTest(
    "Booking automatically assigned unique CHK- code ({$checkinCode})",
    !empty($checkinCode) && str_starts_with($checkinCode, 'CHK-'),
    "Generated code: {$checkinCode}"
);

// --- 3. Test Public Status Endpoint ---
echo "\n--- 3. Testing Booking Public Status & Polling Endpoint ---\n";

$statusRes = requestApi('GET', "/api/v1/agendamentos/{$bookingId}/public-status");
assertTest(
    "Query public booking status for live polling (HTTP 200)",
    $statusRes['status'] === 200 && ($statusRes['json']['data']['agendamento']['id'] ?? 0) === $bookingId,
    json_encode($statusRes['json'])
);

// --- 4. Test Checkin Block on Pending Payment ---
echo "\n--- 4. Testing Checkin Block on Pending Payment ---\n";

$checkinPendingRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/checkin", [
    'identificador' => $checkinCode,
    'origem' => 'TOTEM',
]);

assertTest(
    "Block checkin when booking payment is PENDING (HTTP 400/402)",
    $checkinPendingRes['status'] === 400 && str_contains(strtolower($checkinPendingRes['json']['message'] ?? ''), 'pendente'),
    json_encode($checkinPendingRes['json'])
);

// --- 5. Simulate Payment & Confirm Booking ---
echo "\n--- 5. Simulating Instant PIX Settlement ---\n";

// Generate PIX
$pixRes = requestApi('POST', "/api/v1/agendamentos/{$bookingId}/pix");
$paymentId = (int)($pixRes['json']['data']['pagamento']['id'] ?? 0);
$txid = $pixRes['json']['data']['pix']['txid'] ?? '';

// Settle via webhook
$webhookRes = requestApi('POST', "/api/v1/webhooks/pix", [
    'payment_id' => $paymentId,
    'txid' => $txid,
]);

assertTest(
    "Instant PIX settlement succeeds (HTTP 200)",
    $webhookRes['status'] === 200 && ($webhookRes['json']['data']['status'] ?? '') === 'PAGO',
    json_encode($webhookRes['json'])
);

// Verify status refreshed to CONFIRMADO
$refreshedStatus = requestApi('GET', "/api/v1/agendamentos/{$bookingId}/public-status");
assertTest(
    "Booking status automatically updated to CONFIRMADO after payment",
    ($refreshedStatus['json']['data']['agendamento']['status'] ?? '') === 'CONFIRMADO',
    json_encode($refreshedStatus['json'])
);

// --- 6. Test Successful Checkin via Totem ---
echo "\n--- 6. Testing Successful Checkin via Totem ---\n";

$checkinSuccessRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/checkin", [
    'identificador' => $checkinCode,
    'origem' => 'TOTEM',
]);

assertTest(
    "Execute checkin on confirmed booking via Totem (HTTP 200)",
    $checkinSuccessRes['status'] === 200 &&
    isset($checkinSuccessRes['json']['data']['detalhes']['checkin_em']),
    json_encode($checkinSuccessRes['json'])
);

// --- 7. Test Duplicate Checkin Prevention ---
echo "\n--- 7. Testing Duplicate Checkin Prevention ---\n";

$checkinDuplicateRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/checkin", [
    'identificador' => $checkinCode,
    'origem' => 'TOTEM',
]);

assertTest(
    "Prevent duplicate checkin on already validated booking (HTTP 409 Conflict)",
    $checkinDuplicateRes['status'] === 409 &&
    str_contains(strtolower($checkinDuplicateRes['json']['message'] ?? ''), 'ja foi realizado'),
    json_encode($checkinDuplicateRes['json'])
);

// --- 8. Test Checkin Lookup by Phone Number ---
echo "\n--- 8. Testing Checkin Lookup & History ---\n";

$myBookingsRes = requestApi('POST', "/api/v1/arenas/{$arenaId}/clientes/minhas-reservas", [
    'telefone' => '11988887777',
]);

assertTest(
    "Customer search upcoming bookings by WhatsApp phone (HTTP 200)",
    $myBookingsRes['status'] === 200 && count($myBookingsRes['json']['data']['agendamentos'] ?? []) > 0,
    json_encode($myBookingsRes['json'])
);

// --- 9. Test Audit History in MySQL ---
echo "\n--- 9. Testing Audit History in agendamento_historico ---\n";

$stmtHistory = $pdo->prepare("SELECT * FROM `agendamento_historico` WHERE `agendamento_id` = :id AND `acao` = 'CHECKIN'");
$stmtHistory->execute([':id' => $bookingId]);
$historyRows = $stmtHistory->fetchAll();

assertTest(
    "Checkin event recorded in immutable audit table agendamento_historico",
    count($historyRows) >= 1,
    "Found " . count($historyRows) . " checkin audit records"
);

echo "\n====================================================\n";
echo " PORTAL & TOTEM TEST SUMMARY\n";
echo " Passed: {$passedTests}\n";
echo " Failed: {$failedTests}\n";
echo "====================================================\n";

exit($failedTests > 0 ? 1 : 0);
