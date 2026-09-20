<?php
// Master Arena SaaS - Quadra (Court/Pitch) API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Models\Modalidade;
use App\Models\Quadra;
use App\Services\AuditLogger;

class QuadraController extends BaseController
{
    private Quadra $quadraModel;
    private Modalidade $modalidadeModel;
    private Arena $arenaModel;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->quadraModel = new Quadra();
        $this->modalidadeModel = new Modalidade();
        $this->arenaModel = new Arena();
        $this->auditLogger = new AuditLogger();
    }

    // List courts for a specific arena with optional filters
    public function index(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();

        // Multi-tenant barrier if authenticated as admin or staff
        if ($currentUser && !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para visualizar quadras desta arena.');
            return;
        }

        $modalidadeId = $request->query('modalidade_id') ? (int)$request->query('modalidade_id') : null;
        $status = $request->query('status') ? (string)$request->query('status') : null;

        // For public or client requests without explicit status, default to ATIVO only
        if (!$currentUser || strtoupper($currentUser['perfil'] ?? '') === 'CLIENTE') {
            if ($status === null) {
                $status = 'ATIVO';
            }
        }

        $quadras = $this->quadraModel->listByArena($arenaId, $modalidadeId, $status);

        $this->success([
            'arena_id' => $arenaId,
            'quadras' => $quadras,
            'total' => count($quadras),
        ], 'Lista de quadras recuperada com sucesso.');
    }

    // Show single court details
    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $quadra = $this->quadraModel->findById($id);
        if (!$quadra) {
            $this->notFound('Quadra nao encontrada.');
            return;
        }

        if ($currentUser && !$this->canAccessArena($currentUser, (int)$quadra['arena_id'])) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para visualizar dados desta quadra.');
            return;
        }

        $this->success([
            'quadra' => $quadra,
        ], 'Detalhes da quadra recuperados com sucesso.');
    }

    // Create a new court for an arena
    public function create(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $currentUser = $request->getUser();

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para cadastrar quadras nesta arena.');
            return;
        }

        $this->validate($request, [
            'nome' => 'required|min:2',
            'modalidade_id' => 'required|numeric',
        ]);

        $modalidadeId = (int)$request->input('modalidade_id');
        $modalidade = $this->modalidadeModel->findById($modalidadeId);

        // Validate that modalidade belongs to the same arena
        if (!$modalidade || (int)$modalidade['arena_id'] !== $arenaId) {
            $this->validationError(['modalidade_id' => ['A modalidade informada nao pertence a esta arena ou nao existe.']]);
            return;
        }

        $data = $request->all();
        $data['modalidade_id'] = $modalidadeId;

        $quadraId = $this->quadraModel->create($arenaId, $data);
        $newQuadra = $this->quadraModel->findById($quadraId);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CREATE_QUADRA',
            'quadras',
            $quadraId,
            ['nome' => $data['nome'], 'modalidade_id' => $modalidadeId],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->created([
            'quadra' => $newQuadra,
        ], 'Quadra cadastrada com sucesso.');
    }

    // Update court
    public function update(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $quadra = $this->quadraModel->findById($id);
        if (!$quadra) {
            $this->notFound('Quadra nao encontrada.');
            return;
        }

        $arenaId = (int)$quadra['arena_id'];

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para editar esta quadra.');
            return;
        }

        $data = $request->all();

        // If modalidade_id is updated, verify it belongs to the same arena
        if (isset($data['modalidade_id'])) {
            $modalidadeId = (int)$data['modalidade_id'];
            $modalidade = $this->modalidadeModel->findById($modalidadeId);
            if (!$modalidade || (int)$modalidade['arena_id'] !== $arenaId) {
                $this->validationError(['modalidade_id' => ['A modalidade informada nao pertence a esta arena ou nao existe.']]);
                return;
            }
        }

        // Validate status if provided
        if (isset($data['status'])) {
            $status = strtoupper(trim((string)$data['status']));
            if (!in_array($status, ['ATIVO', 'MANUTENCAO', 'INATIVO'], true)) {
                $this->validationError(['status' => ['O status deve ser ATIVO, MANUTENCAO ou INATIVO.']]);
                return;
            }
            $data['status'] = $status;
        }

        $this->quadraModel->update($id, $data);
        $updatedQuadra = $this->quadraModel->findById($id);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'UPDATE_QUADRA',
            'quadras',
            $id,
            ['updated_fields' => array_keys($data)],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([
            'quadra' => $updatedQuadra,
        ], 'Dados da quadra atualizados com sucesso.');
    }

    // Update court operational status
    public function updateStatus(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $quadra = $this->quadraModel->findById($id);
        if (!$quadra) {
            $this->notFound('Quadra nao encontrada.');
            return;
        }

        $arenaId = (int)$quadra['arena_id'];

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para alterar o status desta quadra.');
            return;
        }

        $status = strtoupper(trim((string)$request->input('status')));
        if (!in_array($status, ['ATIVO', 'MANUTENCAO', 'INATIVO'], true)) {
            $this->validationError(['status' => ['O status deve ser ATIVO, MANUTENCAO ou INATIVO.']]);
            return;
        }

        $this->quadraModel->updateStatus($id, $status);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CHANGE_STATUS_QUADRA',
            'quadras',
            $id,
            ['old_status' => $quadra['status'], 'new_status' => $status],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([
            'id' => $id,
            'status' => $status,
        ], "Status da quadra alterado para {$status} com sucesso.");
    }

    // Delete court
    public function delete(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $quadra = $this->quadraModel->findById($id);
        if (!$quadra) {
            $this->notFound('Quadra nao encontrada.');
            return;
        }

        $arenaId = (int)$quadra['arena_id'];

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para excluir esta quadra.');
            return;
        }

        $this->quadraModel->delete($id);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'DELETE_QUADRA',
            'quadras',
            $id,
            ['nome' => $quadra['nome']],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([], 'Quadra excluida com sucesso.');
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
}
