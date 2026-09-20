<?php
// Master Arena SaaS - Modalidade (Sport Type) API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Models\Modalidade;
use App\Services\AuditLogger;

class ModalidadeController extends BaseController
{
    private Modalidade $modalidadeModel;
    private Arena $arenaModel;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->modalidadeModel = new Modalidade();
        $this->arenaModel = new Arena();
        $this->auditLogger = new AuditLogger();
    }

    // List modalidades for a specific arena
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
            $this->forbidden('Acesso negado. Voce nao possui permissao para visualizar modalidades desta arena.');
            return;
        }

        // Determine if only active modalidades should be returned
        $onlyActive = true;
        if ($currentUser && in_array(strtoupper($currentUser['perfil'] ?? ''), ['SUPERADMIN', 'ADMIN', 'FUNCIONARIO'], true)) {
            $ativoParam = $request->query('ativo');
            if ($ativoParam !== null) {
                $onlyActive = (bool)(int)$ativoParam;
            } else {
                $onlyActive = null;
            }
        }

        $modalidades = $this->modalidadeModel->listByArena($arenaId, $onlyActive);

        $this->success([
            'arena_id' => $arenaId,
            'modalidades' => $modalidades,
        ], 'Lista de modalidades recuperada com sucesso.');
    }

    // Show single modalidade details
    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $modalidade = $this->modalidadeModel->findById($id);
        if (!$modalidade) {
            $this->notFound('Modalidade nao encontrada.');
            return;
        }

        if ($currentUser && !$this->canAccessArena($currentUser, (int)$modalidade['arena_id'])) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para visualizar dados desta modalidade.');
            return;
        }

        $courtsCount = $this->modalidadeModel->countCourts($id);

        $this->success([
            'modalidade' => $modalidade,
            'courts_count' => $courtsCount,
        ], 'Detalhes da modalidade recuperados com sucesso.');
    }

    // Create a new modalidade for an arena
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
            $this->forbidden('Acesso negado. Voce nao possui permissao para cadastrar modalidades nesta arena.');
            return;
        }

        $this->validate($request, [
            'nome' => 'required|min:2',
        ]);

        $nome = trim((string)$request->input('nome'));

        if (!$this->modalidadeModel->isNameAvailable($arenaId, $nome)) {
            $this->validationError(['nome' => ['Ja existe uma modalidade com este nome nesta arena.']]);
            return;
        }

        $data = $request->all();
        $data['nome'] = $nome;

        $modalidadeId = $this->modalidadeModel->create($arenaId, $data);
        $newModalidade = $this->modalidadeModel->findById($modalidadeId);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CREATE_MODALIDADE',
            'modalidades',
            $modalidadeId,
            ['nome' => $nome],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->created([
            'modalidade' => $newModalidade,
        ], 'Modalidade esportiva cadastrada com sucesso.');
    }

    // Update modalidade
    public function update(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $modalidade = $this->modalidadeModel->findById($id);
        if (!$modalidade) {
            $this->notFound('Modalidade nao encontrada.');
            return;
        }

        $arenaId = (int)$modalidade['arena_id'];

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para editar esta modalidade.');
            return;
        }

        $data = $request->all();

        // Check if name is being changed and if it is available
        if (!empty($data['nome'])) {
            $nome = trim((string)$data['nome']);
            if (!$this->modalidadeModel->isNameAvailable($arenaId, $nome, $id)) {
                $this->validationError(['nome' => ['Ja existe outra modalidade com este nome nesta arena.']]);
                return;
            }
            $data['nome'] = $nome;
        }

        $this->modalidadeModel->update($id, $data);
        $updatedModalidade = $this->modalidadeModel->findById($id);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'UPDATE_MODALIDADE',
            'modalidades',
            $id,
            ['updated_fields' => array_keys($data)],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([
            'modalidade' => $updatedModalidade,
        ], 'Modalidade atualizada com sucesso.');
    }

    // Delete modalidade
    public function delete(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $modalidade = $this->modalidadeModel->findById($id);
        if (!$modalidade) {
            $this->notFound('Modalidade nao encontrada.');
            return;
        }

        $arenaId = (int)$modalidade['arena_id'];

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para excluir esta modalidade.');
            return;
        }

        // Check if there are courts linked to this modalidade
        if ($this->modalidadeModel->hasCourts($id)) {
            $this->error('Nao e possivel excluir esta modalidade pois existem quadras vinculadas a ela. Considere inativa-la (ativo = 0).', [], 400);
            return;
        }

        $this->modalidadeModel->delete($id);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'DELETE_MODALIDADE',
            'modalidades',
            $id,
            ['nome' => $modalidade['nome']],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([], 'Modalidade excluida com sucesso.');
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
