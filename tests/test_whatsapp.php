<?php
// Master Arena SaaS - WhatsApp Notifications Engine Test Suite
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
echo " MASTER ARENA - WhatsApp Automated Notifications Tests\n";
echo "====================================================\n";

function requestApi(string $method, string $uri, array $body = [], ?string $token = null): array
{
    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'MasterArena-WhatsAppTest/1.0';

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
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $json = $decoded;
        }
    }

    return [
        'status' => $statusCode,
        'data' => $json,
        'raw' => $raw,
    ];
}

$passed = 0;
$failed = 0;

function assertTest(string $title, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$title}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$title}" . ($detail ? " -> {$detail}" : "") . "\n";
        $failed++;
    }
}

// Authenticate Admin and Staff
$loginAdmin = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'admin@masterarena.com.br',
    'senha' => 'Arena@123456',
]);
$adminToken = $loginAdmin['data']['data']['token'] ?? null;
assertTest('Autenticacao do Administrador da Arena', !empty($adminToken));

$loginStaff = requestApi('POST', '/api/v1/auth/login', [
    'email' => 'atendente@masterarena.com.br',
    'senha' => 'Staff@123456',
]);
$staffToken = $loginStaff['data']['data']['token'] ?? null;
assertTest('Autenticacao do Atendente/Staff da Arena', !empty($staffToken));

echo "\n--- Teste 1: Obter e Atualizar Configuracoes de WhatsApp Multi-Tenant ---\n";
// Read config
$getConfig = requestApi('GET', '/api/v1/arenas/1/notificacoes/whatsapp/config', [], $adminToken);
assertTest('Leitura de configuracoes de WhatsApp da Arena 1', $getConfig['status'] === 200 && isset($getConfig['data']['data']['whatsapp_provider']));

// Update config to use SIMULATOR with all toggles enabled
$updateConfig = requestApi('PUT', '/api/v1/arenas/1/notificacoes/whatsapp/config', [
    'whatsapp_provider' => 'SIMULATOR',
    'whatsapp_api_url' => 'https://api.evolution.local',
    'whatsapp_api_token' => 'test-token-123456',
    'whatsapp_instance' => 'arena-unit-1',
    'whatsapp_notify_pix_pending' => 1,
    'whatsapp_notify_booking_confirmed' => 1,
    'whatsapp_notify_booking_cancelled' => 1,
    'whatsapp_notify_game_reminder' => 1,
    'whatsapp_reminder_hours_before' => 2,
], $adminToken);

assertTest('Atualizacao das configuracoes para SIMULATOR e toggles ativos', $updateConfig['status'] === 200);

// Staff RBAC check - Staff/atendente is forbidden from modifying administrative WhatsApp settings
$staffForbidden = requestApi('PUT', '/api/v1/arenas/1/notificacoes/whatsapp/config', [
    'whatsapp_provider' => 'SIMULATOR',
], $staffToken);
assertTest('Bloqueio RBAC: Staff sem perfil ADMIN nao pode alterar configuracoes de WhatsApp', $staffForbidden['status'] === 403);

echo "\n--- Teste 2: Disparo de Mensagem de Teste (Driver SIMULATOR) ---\n";
$testMsg = requestApi('POST', '/api/v1/arenas/1/notificacoes/whatsapp/testar', [
    'telefone' => '11988887777',
    'mensagem' => 'Teste automatizado de notificacao via simulador Master Arena.',
], $adminToken);

assertTest('Disparo de mensagem de teste com sucesso HTTP 200', $testMsg['status'] === 200);
assertTest('Resposta contem status ENVIADO e provider SIMULATOR', ($testMsg['data']['data']['status'] ?? '') === 'ENVIADO' && ($testMsg['data']['data']['provider'] ?? '') === 'SIMULATOR');
assertTest('Identificador de mensagem externa simulado presente', !empty($testMsg['data']['data']['external_message_id']));

echo "\n--- Teste 3: Disparo Automatico de PIX Pendente ao Gerar Pagamento ---\n";
// Create a new booking for a random future date to prevent slot collisions
$testDatePix = date('Y-m-d', strtotime('+' . rand(20, 60) . ' days'));
$createBooking = requestApi('POST', '/api/v1/arenas/1/agendamentos', [
    'quadra_id' => 1,
    'cliente_nome' => 'Carlos WhatsApp Atleta',
    'cliente_email' => 'carlos.wa@teste.com',
    'cliente_telefone' => '11977776666',
    'data' => $testDatePix,
    'hora_inicio' => '18:00',
    'hora_fim' => '19:00',
    'tipo_reserva' => 'AVULSO',
], $adminToken);

$bookingId = $createBooking['data']['data']['agendamento']['id'] ?? null;
assertTest('Criacao de agendamento para teste de PIX', !empty($bookingId), $createBooking['data']['message'] ?? 'Erro desconhecido');

$pdo = Connection::getInstance();

if ($bookingId) {
    // Generate PIX for booking (HTTP 201 Created)
    $pixReq = requestApi('POST', "/api/v1/agendamentos/{$bookingId}/pix", [], $adminToken);
    $pixCode = $pixReq['data']['data']['pix']['copia_e_cola'] ?? $pixReq['data']['data']['pix_copia_cola'] ?? '';
    $paymentId = $pixReq['data']['data']['pagamento']['id'] ?? $pixReq['data']['data']['pagamento_id'] ?? null;

    assertTest('Geracao de cobranca PIX para o agendamento', ($pixReq['status'] === 200 || $pixReq['status'] === 201) && !empty($pixCode));

    // Check if notification record was logged in DB
    $stmt = $pdo->prepare("SELECT * FROM notificacoes_whatsapp WHERE agendamento_id = :aid AND tipo = 'PIX_PENDENTE' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['aid' => $bookingId]);
    $pixNotification = $stmt->fetch(\PDO::FETCH_ASSOC);

    assertTest('Registro de notificacao PIX_PENDENTE gravado com status ENVIADO', !empty($pixNotification) && $pixNotification['status'] === 'ENVIADO');
    assertTest('Notificacao PIX contem copia e cola na mensagem', !empty($pixNotification) && (strpos($pixNotification['mensagem'], 'PIX Copia e Cola') !== false || strpos($pixNotification['mensagem'], '000201') !== false));
}

echo "\n--- Teste 4: Disparo Automatico de Confirmacao de Reserva ao Liquidar Pagamento ---\n";
if ($bookingId) {
    // Settle payment as Staff/Admin
    if (!$paymentId) {
        $paymentId = $pixReq['data']['data']['pagamento']['id'] ?? $pixReq['data']['data']['pagamento_id'] ?? null;
    }
    assertTest('Pagamento vinculado ao agendamento localizado', !empty($paymentId));

    if ($paymentId) {
        $settleReq = requestApi('POST', "/api/v1/pagamentos/{$paymentId}/confirmar", [], $adminToken);
        assertTest('Confirmacao de pagamento pelo operador', $settleReq['status'] === 200);

        // Check if RESERVA_CONFIRMADA notification was created
        $stmt = $pdo->prepare("SELECT * FROM notificacoes_whatsapp WHERE agendamento_id = :aid AND tipo = 'RESERVA_CONFIRMADA' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['aid' => $bookingId]);
        $confirmedNotif = $stmt->fetch(\PDO::FETCH_ASSOC);

        assertTest('Notificacao RESERVA_CONFIRMADA gravada com status ENVIADO', !empty($confirmedNotif) && $confirmedNotif['status'] === 'ENVIADO');
        assertTest('Mensagem de confirmacao contem codigo de check-in', !empty($confirmedNotif) && strpos($confirmedNotif['mensagem'], 'Check-in:') !== false);
    }
}

echo "\n--- Teste 5: Disparo Automatico de Cancelamento de Reserva ---\n";
// Create another booking to cancel
$testDateCanc = date('Y-m-d', strtotime('+' . rand(70, 150) . ' days'));
$createBooking2 = requestApi('POST', '/api/v1/arenas/1/agendamentos', [
    'quadra_id' => 1,
    'cliente_nome' => 'Marcos Cancelamento',
    'cliente_email' => 'marcos.canc@teste.com',
    'cliente_telefone' => '11966665555',
    'data' => $testDateCanc,
    'hora_inicio' => '20:00',
    'hora_fim' => '21:00',
    'tipo_reserva' => 'AVULSO',
], $adminToken);

$booking2Id = $createBooking2['data']['data']['agendamento']['id'] ?? null;
assertTest('Criacao de segundo agendamento para teste de cancelamento', !empty($booking2Id));

if ($booking2Id) {
    $cancelReq = requestApi('POST', "/api/v1/agendamentos/{$booking2Id}/cancelar", [
        'motivo' => 'Teste automatizado de cancelamento de horario',
    ], $adminToken);
    assertTest('Cancelamento do agendamento realizado', $cancelReq['status'] === 200);

    // Verify cancellation notification
    $stmt = $pdo->prepare("SELECT * FROM notificacoes_whatsapp WHERE agendamento_id = :aid AND tipo = 'CANCELAMENTO' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['aid' => $booking2Id]);
    $cancelledNotif = $stmt->fetch(\PDO::FETCH_ASSOC);

    assertTest('Notificacao CANCELAMENTO gravada com status ENVIADO', !empty($cancelledNotif) && $cancelledNotif['status'] === 'ENVIADO');
    assertTest('Mensagem de cancelamento informa o atleta', !empty($cancelledNotif) && strpos($cancelledNotif['mensagem'], 'Cancelamento de Reserva') !== false);
}

echo "\n--- Teste 6: Motor de Lembretes de Jogo e Bloqueio de Duplicidade ---\n";
$today = date('Y-m-d');
$nowPlus1Hour = date('H:00', strtotime('+1 hour'));
$nowPlus2Hours = date('H:00', strtotime('+2 hours'));

// Ensure slot is free by cleaning conflicting bookings on this specific slot
$pdo->prepare("DELETE FROM agendamentos WHERE arena_id = 1 AND data = :d AND hora_inicio = :h")->execute([
    'd' => $today,
    'h' => $nowPlus1Hour
]);

$createReminderBooking = requestApi('POST', '/api/v1/arenas/1/agendamentos', [
    'quadra_id' => 1,
    'cliente_nome' => 'Fernanda Lembrete',
    'cliente_email' => 'fernanda@teste.com',
    'cliente_telefone' => '11955554444',
    'data' => $today,
    'hora_inicio' => $nowPlus1Hour,
    'hora_fim' => $nowPlus2Hours,
    'tipo_reserva' => 'AVULSO',
], $adminToken);

$reminderBookingId = $createReminderBooking['data']['data']['agendamento']['id'] ?? null;
if ($reminderBookingId) {
    // Set status to CONFIRMADO directly for test
    $pdo->prepare("UPDATE agendamentos SET status = 'CONFIRMADO' WHERE id = :id")->execute(['id' => $reminderBookingId]);
}
assertTest('Criacao de agendamento CONFIRMADO proximo para disparo de lembrete', !empty($reminderBookingId));

// Run batch reminder processing
$reminderRun1 = requestApi('POST', '/api/v1/arenas/1/notificacoes/whatsapp/processar-lembretes', [], $adminToken);
assertTest('Execucao do lote de processamento de lembretes', $reminderRun1['status'] === 200);

// Verify reminder notification was dispatched
if ($reminderBookingId) {
    $stmt = $pdo->prepare("SELECT * FROM notificacoes_whatsapp WHERE agendamento_id = :aid AND tipo = 'LEMBRETE_JOGO' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['aid' => $reminderBookingId]);
    $remNotif = $stmt->fetch(\PDO::FETCH_ASSOC);

    assertTest('Notificacao LEMBRETE_JOGO gravada com status ENVIADO', !empty($remNotif) && $remNotif['status'] === 'ENVIADO');

    // Run batch reminder processing a SECOND time to verify anti-duplicity shield
    $reminderRun2 = requestApi('POST', '/api/v1/arenas/1/notificacoes/whatsapp/processar-lembretes', [], $adminToken);
    assertTest('Segunda execucao do lote de lembretes', $reminderRun2['status'] === 200);

    // Count how many LEMBRETE_JOGO exist for this booking
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notificacoes_whatsapp WHERE agendamento_id = :aid AND tipo = 'LEMBRETE_JOGO'");
    $stmtCount->execute(['aid' => $reminderBookingId]);
    $remCount = (int) $stmtCount->fetchColumn();

    assertTest('Escudo Anti-Duplicidade: Lembrete enviado apenas 1 vez (sem duplicatas)', $remCount === 1);
}

echo "\n--- Teste 7: Auditoria e Listagem de Notificacoes Multi-Tenant ---\n";
$listNotifs = requestApi('GET', '/api/v1/arenas/1/notificacoes/whatsapp?limit=10', [], $adminToken);
assertTest('Listagem de notificacoes da Arena 1 com paginacao', $listNotifs['status'] === 200 && isset($listNotifs['data']['data']['notificacoes']));

$notifsList = $listNotifs['data']['data']['notificacoes'] ?? [];
assertTest('Historico contem disparos recentes registrados', count($notifsList) > 0);

// Multi-tenant check: Arena 2 listing by Arena 1 admin
$listArena2 = requestApi('GET', '/api/v1/arenas/2/notificacoes/whatsapp', [], $adminToken);
assertTest('Isolamento Multi-Tenant: Admin da Arena 1 e bloqueado (403) na Arena 2', $listArena2['status'] === 403);

echo "\n====================================================\n";
echo " RESUMO DOS TESTES DE NOTIFICACOES WHATSAPP (ETAPA 10)\n";
echo "====================================================\n";
echo " Total de testes : " . ($passed + $failed) . "\n";
echo " Aprovados       : {$passed}\n";
echo " Falhas          : {$failed}\n";
echo "====================================================\n";

if ($failed > 0) {
    exit(1);
}
