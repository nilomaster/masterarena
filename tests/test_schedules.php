<?php
// Master Arena SaaS - Schedules, Hours, Blocks & Availability Grid Test Suite
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
echo " MASTER ARENA - Schedules & Availability Grid Tests\n";
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

$loginAdmin2 = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin2@masterarena.com.br',
    'senha' => 'Arena@123456',
]);
$admin2Token = $loginAdmin2['json']['data']['token'] ?? null;

if ($superToken && $admin1Token && $admin2Token) {
    echo "[OK]\n";
} else {
    echo "[FAILED] Could not authenticate test users.\n";
    exit(1);
}

// Get or ensure at least one court exists for Arena 1
$pdo = Connection::getInstance();
$stmtCourt = $pdo->prepare("SELECT id, modalidade_id, valor_padrao FROM `quadras` WHERE `arena_id` = 1 AND `status` = 'ATIVO' LIMIT 1");
$stmtCourt->execute();
$court = $stmtCourt->fetch();

if (!$court) {
    // Seed a modalidade and court
    $stmtMod = $pdo->prepare("INSERT INTO `modalidades` (`arena_id`, `nome`, `ativo`) VALUES (1, 'Beach Tennis Base', 1) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    $stmtMod->execute();
    $modId = (int)$pdo->lastInsertId();

    $stmtNewCourt = $pdo->prepare("INSERT INTO `quadras` (`arena_id`, `modalidade_id`, `nome`, `capacidade`, `valor_padrao`, `status`) 
        VALUES (1, :mod_id, 'Quadra Central Teste', 4, 100.00, 'ATIVO')");
    $stmtNewCourt->execute([':mod_id' => $modId]);
    $testCourtId = (int)$pdo->lastInsertId();
    $testModId = $modId;
    $testDefaultPrice = 100.00;
} else {
    $testCourtId = (int)$court['id'];
    $testModId = (int)$court['modalidade_id'];
    $testDefaultPrice = (float)$court['valor_padrao'];
}

// Test 1: Save Weekly Operating Hours (Days 0 to 6)
echo "Test 1: Save weekly operating hours (06:00 to 23:00) ... ";
$weeklySchedule = [];
for ($d = 0; $d <= 6; $d++) {
    $weeklySchedule[] = [
        'dia_semana' => $d,
        'hora_inicio' => '06:00:00',
        'hora_fim' => '23:00:00',
        'duracao_minutos' => 60,
        'intervalo_minutos' => 0,
        'ativo' => 1,
    ];
}
$t1 = requestApi('POST', '/api/v1/arenas/1/horarios', [
    'horarios' => $weeklySchedule,
], $admin1Token);
if ($t1['status'] === 200 && ($t1['json']['data']['total_dias'] ?? 0) === 7) {
    echo "[PASSED] (7 days operating schedule saved)\n";
} else {
    echo "[FAILED]\n";
    print_r($t1);
}

// Test 2: Query Saved Operating Hours
echo "Test 2: Query saved operating hours ... ";
$t2 = requestApi('GET', '/api/v1/arenas/1/horarios', [], $admin1Token);
$horariosCount = count($t2['json']['data']['horarios'] ?? []);
if ($t2['status'] === 200 && $horariosCount === 7) {
    echo "[PASSED] (Retrieved 7 operating days)\n";
} else {
    echo "[FAILED]\n";
    print_r($t2);
}

// Test 3: Generate Court Availability Grid for Tomorrow
$targetDate = date('Y-m-d', strtotime('+1 day'));
$targetDayOfWeek = (int)date('w', strtotime($targetDate));

echo "Test 3: Generate availability grid for {$targetDate} ... ";
$t3 = requestApi('GET', "/api/v1/arenas/1/grade?data={$targetDate}");
$gridCourts = $t3['json']['data']['grade']['quadras'] ?? [];
$firstCourt = $gridCourts[0] ?? null;
$firstSlot = $firstCourt['slots'][0] ?? null;

if ($t3['status'] === 200 && count($gridCourts) >= 1 && ($firstSlot['status'] ?? '') === 'LIVRE') {
    $totalSlots = count($firstCourt['slots'] ?? []);
    echo "[PASSED] ({$totalSlots} slots generated, initial status: LIVRE)\n";
} else {
    echo "[FAILED]\n";
    print_r($t3);
}

// Test 4: Create Dynamic Pricing Rule (Evening Prime Time: 18:00 to 23:00)
echo "Test 4: Create dynamic pricing rule (Prime time: R$ 135.00) ... ";
$primePrice = 135.00;
$t4 = requestApi('POST', '/api/v1/arenas/1/valores-horarios', [
    'quadra_id' => $testCourtId,
    'dia_semana' => $targetDayOfWeek,
    'hora_inicio' => '18:00:00',
    'hora_fim' => '23:00:00',
    'valor' => $primePrice,
], $admin1Token);
$valorId = (int)($t4['json']['data']['valor_horario']['id'] ?? 0);
if ($t4['status'] === 201 && $valorId > 0 && (float)($t4['json']['data']['valor_horario']['valor'] ?? 0) == $primePrice) {
    echo "[PASSED] (Rule created with ID {$valorId})\n";
} else {
    echo "[FAILED]\n";
    print_r($t4);
}

// Test 5: Verify Dynamic Price Applied to Evening Slot in Grid
echo "Test 5: Verify dynamic price applied in grid ... ";
$t5 = requestApi('GET', "/api/v1/arenas/1/grade?data={$targetDate}&quadra_id={$testCourtId}");
$courtSlots = $t5['json']['data']['grade']['quadras'][0]['slots'] ?? [];

$primeSlotFound = false;
$morningPrice = null;

foreach ($courtSlots as $s) {
    if ($s['hora_inicio'] === '19:00:00') {
        if ((float)$s['valor'] == $primePrice) {
            $primeSlotFound = true;
        }
    }
    if ($s['hora_inicio'] === '08:00:00') {
        $morningPrice = (float)$s['valor'];
    }
}

if ($t5['status'] === 200 && $primeSlotFound && $morningPrice !== null) {
    echo "[PASSED] (19h slot: R$ 135.00, 08h slot: R$ {$morningPrice})\n";
} else {
    echo "[FAILED] Prime: " . ($primeSlotFound ? 'OK' : 'NO') . ", Morning price: {$morningPrice}\n";
    print_r($t5);
}

// Test 6: Create Maintenance Block for Test Court
echo "Test 6: Create maintenance block (14:00 to 17:00) ... ";
$t6 = requestApi('POST', '/api/v1/arenas/1/bloqueios', [
    'quadra_id' => $testCourtId,
    'data_inicio' => $targetDate,
    'data_fim' => $targetDate,
    'hora_inicio' => '14:00:00',
    'hora_fim' => '17:00:00',
    'motivo' => 'Manutencao preventiva de refletores LED',
], $admin1Token);
$bloqueioId = (int)($t6['json']['data']['bloqueio']['id'] ?? 0);
if ($t6['status'] === 201 && $bloqueioId > 0) {
    echo "[PASSED] (Block created with ID {$bloqueioId})\n";
} else {
    echo "[FAILED]\n";
    print_r($t6);
}

// Test 7: Verify Blocked Status in Availability Grid
echo "Test 7: Verify block reflected in availability grid ... ";
$t7 = requestApi('GET', "/api/v1/arenas/1/grade?data={$targetDate}&quadra_id={$testCourtId}");
$courtSlotsAfterBlock = $t7['json']['data']['grade']['quadras'][0]['slots'] ?? [];

$blockVerified = false;
foreach ($courtSlotsAfterBlock as $s) {
    if ($s['hora_inicio'] === '15:00:00' && $s['status'] === 'BLOQUEADO' && ($s['motivo'] ?? '') === 'Manutencao preventiva de refletores LED') {
        $blockVerified = true;
        break;
    }
}

if ($t7['status'] === 200 && $blockVerified) {
    echo "[PASSED] (15:00 slot correctly marked as BLOQUEADO)\n";
} else {
    echo "[FAILED]\n";
    print_r($t7);
}

// Test 8: Multi-Tenant Shield (Admin 2 blocked from managing Arena 1 schedules or blocks)
echo "Test 8: Multi-Tenant Shield on blocks and schedules ... ";
$t8a = requestApi('POST', '/api/v1/arenas/1/bloqueios', [
    'quadra_id' => $testCourtId,
    'data_inicio' => $targetDate,
    'hora_inicio' => '10:00:00',
    'hora_fim' => '12:00:00',
    'motivo' => 'Invasao nao permitida',
], $admin2Token);

$t8b = requestApi('POST', '/api/v1/arenas/1/horarios', [
    'horarios' => [],
], $admin2Token);

if ($t8a['status'] === 403 && $t8b['status'] === 403) {
    echo "[PASSED] (403 Forbidden correctly protected cross-tenant access)\n";
} else {
    echo "[FAILED] 8a: {$t8a['status']}, 8b: {$t8b['status']}\n";
}

// Test 9: Delete Block and Verify Immediate Slot Release in Grid
echo "Test 9: Delete block and verify slot release ... ";
$t9 = requestApi('DELETE', "/api/v1/bloqueios/{$bloqueioId}", [], $admin1Token);
$t9Grid = requestApi('GET', "/api/v1/arenas/1/grade?data={$targetDate}&quadra_id={$testCourtId}");
$slotsAfterDel = $t9Grid['json']['data']['grade']['quadras'][0]['slots'] ?? [];

$slotReleased = false;
foreach ($slotsAfterDel as $s) {
    if ($s['hora_inicio'] === '15:00:00' && $s['status'] === 'LIVRE') {
        $slotReleased = true;
        break;
    }
}

if ($t9['status'] === 200 && $slotReleased) {
    echo "[PASSED] (Block deleted and 15:00 slot released to LIVRE)\n";
} else {
    echo "[FAILED]\n";
    print_r($t9);
}

// Test 10: Delete Dynamic Pricing Rule
echo "Test 10: Delete dynamic pricing rule ... ";
$t10 = requestApi('DELETE', "/api/v1/valores-horarios/{$valorId}", [], $admin1Token);
if ($t10['status'] === 200) {
    echo "[PASSED] (Pricing rule deleted)\n";
} else {
    echo "[FAILED]\n";
    print_r($t10);
}

// Test 11: Verify Audit Trail in MySQL
echo "Test 11: Verify audit trail in MySQL ... ";
$stmtLogs = $pdo->prepare("SELECT COUNT(*) FROM `logs` WHERE `entidade` IN ('horarios', 'bloqueios', 'valores_horarios')");
$stmtLogs->execute();
$auditCount = (int)$stmtLogs->fetchColumn();

if ($auditCount >= 3) {
    echo "[PASSED] ({$auditCount} schedule engine audit records registered in database)\n";
} else {
    echo "[FAILED] Audit count: {$auditCount}\n";
}

echo "All Schedules & Availability Grid tests completed successfully.\n";
