<?php
// Master Arena SaaS - Arena Management API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Services\AuditLogger;

class ArenaController extends BaseController
{
    private Arena $arenaModel;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->arenaModel = new Arena();
        $this->auditLogger = new AuditLogger();
    }

    // List all arenas with pagination (Superadmin only)
    public function index(Request $request): void
    {
        $status = $request->query('status');
        $page = max(1, (int)$request->query('page', 1));
        $limit = min(100, max(1, (int)$request->query('limit', 20)));
        $offset = ($page - 1) * $limit;

        $arenas = $this->arenaModel->listAll($status ? (string)$status : null, $limit, $offset);
        $total = $this->arenaModel->countAll($status ? (string)$status : null);
        $totalPages = (int)ceil($total / $limit);

        $this->success([
            'arenas' => $arenas,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
            ],
        ], 'Lista de arenas recuperada com sucesso.');
    }

    // Show single arena details (Superadmin or arena Admin)
    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $arena = $this->arenaModel->findById($id);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        // Multi-tenant barrier: arena admin cannot access another arena
        if (!$this->canAccessArena($currentUser, $id)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para visualizar dados desta arena.');
            return;
        }

        $settings = $this->arenaModel->getSettings($id);

        $this->success([
            'arena' => $arena,
            'settings' => $settings,
        ], 'Dados da arena recuperados com sucesso.');
    }

    // Public lookup by slug (for client booking portal)
    public function bySlug(Request $request): void
    {
        $slug = (string)$request->getParam('slug');

        $arena = $this->arenaModel->findBySlug($slug);
        if (!$arena || !$this->arenaModel->isActive($arena)) {
            $this->notFound('Arena nao encontrada ou indisponivel no momento.');
            return;
        }

        $settings = $this->arenaModel->getSettings((int)$arena['id']);

        // Filter public safe settings
        $publicSettings = [
            'horario_abertura' => $settings['horario_abertura'] ?? '06:00',
            'horario_fechamento' => $settings['horario_fechamento'] ?? '23:00',
            'intervalo_minutos' => $settings['intervalo_minutos'] ?? '60',
            'dias_funcionamento' => $settings['dias_funcionamento'] ?? 'Segunda a Domingo',
        ];

        $this->success([
            'arena' => Arena::formatSafePublic($arena),
            'settings' => $publicSettings,
        ], 'Dados publicos da arena recuperados.');
    }

    // Create new arena (Superadmin only)
    public function create(Request $request): void
    {
        $this->validate($request, [
            'nome_arena' => 'required|min:3',
            'slug' => 'required|min:2',
            'whatsapp' => 'required',
        ]);

        $slug = $this->sanitizeSlug((string)$request->input('slug'));

        if (!$this->arenaModel->isSlugAvailable($slug)) {
            $this->validationError(['slug' => ['O slug informado ja esta em uso por outra arena.']]);
            return;
        }

        $data = $request->all();
        $data['slug'] = $slug;

        $arenaId = $this->arenaModel->create($data);
        $newArena = $this->arenaModel->findById($arenaId);

        $currentUser = $request->getUser();
        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CREATE_ARENA',
            'arenas',
            $arenaId,
            ['nome_arena' => $data['nome_arena'], 'slug' => $slug],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->created([
            'arena' => $newArena,
        ], 'Arena cadastrada com sucesso.');
    }

    // Update arena details (Superadmin or arena Admin)
    public function update(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $arena = $this->arenaModel->findById($id);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        // Multi-tenant barrier
        if (!$this->canAccessArena($currentUser, $id)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para editar dados desta arena.');
            return;
        }

        $data = $request->all();

        // Check slug uniqueness if slug is being updated
        if (!empty($data['slug'])) {
            $newSlug = $this->sanitizeSlug((string)$data['slug']);
            if (!$this->arenaModel->isSlugAvailable($newSlug, $id)) {
                $this->validationError(['slug' => ['O slug informado ja esta em uso por outra arena.']]);
                return;
            }
            $data['slug'] = $newSlug;
        }

        $this->arenaModel->update($id, $data);
        $updatedArena = $this->arenaModel->findById($id);

        $this->auditLogger->log(
            $id,
            $currentUser ? (int)$currentUser['id'] : null,
            'UPDATE_ARENA',
            'arenas',
            $id,
            ['updated_fields' => array_keys($data)],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([
            'arena' => $updatedArena,
        ], 'Dados da arena atualizados com sucesso.');
    }

    // Update arena operational status (Superadmin only)
    public function updateStatus(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $status = strtoupper(trim((string)$request->input('status')));

        if (!in_array($status, ['ATIVO', 'BLOQUEADO', 'INATIVO'], true)) {
            $this->validationError(['status' => ['O status deve ser ATIVO, BLOQUEADO ou INATIVO.']]);
            return;
        }

        $arena = $this->arenaModel->findById($id);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $this->arenaModel->updateStatus($id, $status);
        $currentUser = $request->getUser();

        $this->auditLogger->log(
            $id,
            $currentUser ? (int)$currentUser['id'] : null,
            'CHANGE_STATUS_ARENA',
            'arenas',
            $id,
            ['old_status' => $arena['status'], 'new_status' => $status],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([
            'id' => $id,
            'status' => $status,
        ], "Status da arena alterado para {$status} com sucesso.");
    }

    // Get tenant operational settings
    public function getSettings(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $arena = $this->arenaModel->findById($id);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        if (!$this->canAccessArena($currentUser, $id)) {
            $this->forbidden('Acesso negado. Voce nao pode consultar configuracoes desta arena.');
            return;
        }

        $settings = $this->arenaModel->getSettings($id);
        $this->success([
            'arena_id' => $id,
            'settings' => $settings,
        ], 'Configuracoes da arena recuperadas com sucesso.');
    }

    // Update tenant operational settings in batch
    public function updateSettings(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $arena = $this->arenaModel->findById($id);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        if (!$this->canAccessArena($currentUser, $id)) {
            $this->forbidden('Acesso negado. Voce nao pode alterar configuracoes desta arena.');
            return;
        }

        $settings = $request->input('settings');
        if (!is_array($settings)) {
            // Support flat body key-value pairs
            $settings = $request->all();
        }

        $this->arenaModel->setManySettings($id, $settings);
        $savedSettings = $this->arenaModel->getSettings($id);

        $this->auditLogger->log(
            $id,
            $currentUser ? (int)$currentUser['id'] : null,
            'UPDATE_SETTINGS_ARENA',
            'arenas',
            $id,
            ['keys' => array_keys($settings)],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([
            'arena_id' => $id,
            'settings' => $savedSettings,
        ], 'Configuracoes da arena atualizadas com sucesso.');
    }

    // Helper to verify multi-tenant access rights
    private function canAccessArena(?array $user, int $targetArenaId): bool
    {
        if (!$user) {
            return false;
        }

        $perfil = strtoupper($user['perfil'] ?? '');
        if ($perfil === 'SUPERADMIN') {
            return true;
        }

        $userArenaId = isset($user['arena_id']) ? (int)$user['arena_id'] : null;
        return $userArenaId === $targetArenaId;
    }

    // Sanitize string to clean URL-safe slug
    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}
