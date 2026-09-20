<?php
// Master Arena SaaS - WhatsApp Notifications Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Models\NotificacaoWhatsApp;
use App\Services\WhatsAppService;

class WhatsAppNotificationController extends BaseController
{
    private Arena $arenaModel;
    private NotificacaoWhatsApp $notificacaoModel;
    private WhatsAppService $whatsAppService;

    public function __construct()
    {
        $this->arenaModel = new Arena();
        $this->notificacaoModel = new NotificacaoWhatsApp();
        $this->whatsAppService = new WhatsAppService();
    }

    // Helper: Check multi-tenant permission
    private function canAccessArena(?array $user, int $arenaId): bool
    {
        if (!$user) {
            return false;
        }

        $perfil = strtoupper($user['perfil'] ?? $user['tipo'] ?? '');
        if ($perfil === 'SUPERADMIN') {
            return true;
        }

        return (int)($user['arena_id'] ?? 0) === $arenaId;
    }

    // List WhatsApp notification logs for an arena
    public function index(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar notificacoes desta arena.');
            return;
        }

        $filters = [];
        if ($request->query('tipo')) $filters['tipo'] = (string)$request->query('tipo');
        if ($request->query('status')) $filters['status'] = (string)$request->query('status');
        if ($request->query('telefone')) $filters['telefone'] = (string)$request->query('telefone');

        $page = max(1, (int)($request->query('page') ?? 1));
        $perPage = min(100, max(1, (int)($request->query('per_page') ?? 30)));
        $offset = ($page - 1) * $perPage;

        $notifs = $this->notificacaoModel->listByArena($arenaId, $filters, $perPage, $offset);
        $total = $this->notificacaoModel->countByArena($arenaId, $filters);

        $this->ok([
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'notificacoes' => $notifs,
        ], 'Notificacoes recuperadas com sucesso.');
    }

    // Get WhatsApp gateway configurations for an arena
    public function getConfig(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar configuracoes desta arena.');
            return;
        }

        $settings = $this->arenaModel->getSettings($arenaId);

        $this->ok([
            'whatsapp_enabled' => ($settings['whatsapp_enabled'] ?? '1') === '1',
            'whatsapp_provider' => $settings['whatsapp_provider'] ?? 'SIMULATOR',
            'whatsapp_api_url' => $settings['whatsapp_api_url'] ?? '',
            'whatsapp_api_token' => !empty($settings['whatsapp_api_token']) ? '********' : '',
            'whatsapp_instance' => $settings['whatsapp_instance'] ?? '',
            'whatsapp_notify_pix' => ($settings['whatsapp_notify_pix'] ?? '1') === '1',
            'whatsapp_notify_pix_pending' => ($settings['whatsapp_notify_pix'] ?? '1') === '1',
            'whatsapp_notify_confirmed' => ($settings['whatsapp_notify_confirmed'] ?? '1') === '1',
            'whatsapp_notify_booking_confirmed' => ($settings['whatsapp_notify_confirmed'] ?? '1') === '1',
            'whatsapp_notify_reminder' => ($settings['whatsapp_notify_reminder'] ?? '1') === '1',
            'whatsapp_notify_game_reminder' => ($settings['whatsapp_notify_reminder'] ?? '1') === '1',
            'whatsapp_notify_cancelled' => ($settings['whatsapp_notify_cancelled'] ?? '1') === '1',
            'whatsapp_notify_booking_cancelled' => ($settings['whatsapp_notify_cancelled'] ?? '1') === '1',
            'whatsapp_reminder_hours' => (int)($settings['whatsapp_reminder_hours'] ?? 2),
            'whatsapp_reminder_hours_before' => (int)($settings['whatsapp_reminder_hours'] ?? 2),
        ], 'Configuracoes de WhatsApp recuperadas com sucesso.');
    }

    // Update WhatsApp gateway configurations for an arena
    public function updateConfig(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para alterar configuracoes desta arena.');
            return;
        }

        $body = $request->getBody();
        $updates = [];

        if (isset($body['whatsapp_enabled'])) {
            $updates['whatsapp_enabled'] = $body['whatsapp_enabled'] ? '1' : '0';
        }
        if (isset($body['whatsapp_provider'])) {
            $updates['whatsapp_provider'] = strtoupper(trim((string)$body['whatsapp_provider']));
        }
        if (isset($body['whatsapp_api_url'])) {
            $updates['whatsapp_api_url'] = trim((string)$body['whatsapp_api_url']);
        }
        if (!empty($body['whatsapp_api_token']) && $body['whatsapp_api_token'] !== '********') {
            $updates['whatsapp_api_token'] = trim((string)$body['whatsapp_api_token']);
        }
        if (isset($body['whatsapp_instance'])) {
            $updates['whatsapp_instance'] = trim((string)$body['whatsapp_instance']);
        }
        if (isset($body['whatsapp_notify_pix']) || isset($body['whatsapp_notify_pix_pending'])) {
            $val = $body['whatsapp_notify_pix'] ?? $body['whatsapp_notify_pix_pending'];
            $updates['whatsapp_notify_pix'] = $val ? '1' : '0';
        }
        if (isset($body['whatsapp_notify_confirmed']) || isset($body['whatsapp_notify_booking_confirmed'])) {
            $val = $body['whatsapp_notify_confirmed'] ?? $body['whatsapp_notify_booking_confirmed'];
            $updates['whatsapp_notify_confirmed'] = $val ? '1' : '0';
        }
        if (isset($body['whatsapp_notify_reminder']) || isset($body['whatsapp_notify_game_reminder'])) {
            $val = $body['whatsapp_notify_reminder'] ?? $body['whatsapp_notify_game_reminder'];
            $updates['whatsapp_notify_reminder'] = $val ? '1' : '0';
        }
        if (isset($body['whatsapp_notify_cancelled']) || isset($body['whatsapp_notify_booking_cancelled'])) {
            $val = $body['whatsapp_notify_cancelled'] ?? $body['whatsapp_notify_booking_cancelled'];
            $updates['whatsapp_notify_cancelled'] = $val ? '1' : '0';
        }
        if (isset($body['whatsapp_reminder_hours']) || isset($body['whatsapp_reminder_hours_before'])) {
            $val = $body['whatsapp_reminder_hours'] ?? $body['whatsapp_reminder_hours_before'];
            $updates['whatsapp_reminder_hours'] = max(1, min(48, (int)$val));
        }

        $this->arenaModel->setManySettings($arenaId, $updates);

        $this->ok($this->arenaModel->getSettings($arenaId), 'Configuracoes de WhatsApp salvas com sucesso.');
    }

    // Send a test message to verify connection
    public function testMessage(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para realizar testes nesta arena.');
            return;
        }

        $body = $request->getBody();
        $phone = trim((string)($body['telefone'] ?? ''));

        if (empty($phone)) {
            $this->badRequest('Telefone de destino e obrigatorio.');
            return;
        }

        $result = $this->whatsAppService->sendTestMessage($arenaId, $phone);

        if (!$result['success']) {
            $this->badRequest($result['message']);
            return;
        }

        $this->ok($result, $result['message']);
    }

    // Process pending reminders for today
    public function processReminders(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para processar lembretes desta arena.');
            return;
        }

        $result = $this->whatsAppService->processPendingReminders($arenaId);
        $result['lembretes_enviados'] = $result['dispatched_count'] ?? 0;

        $this->ok($result, $result['message']);
    }
}
