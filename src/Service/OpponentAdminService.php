<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\OpponentsTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * OpponentAdminService
 *
 * Owns the administrative opponent management slice used by the admin
 * controller: list queries, add/edit persistence, popup payload generation, and
 * AJAX search result shaping.
 *
 * Notes:
 * - Keep HTTP concerns (Flash/Redirect/allowMethod) in controllers.
 * - Preserve response keys used by popup and search JavaScript integrations.
 * - Reuse OpponentService/PlaceService helpers where possible.
 */
class OpponentAdminService
{
    private RbacPermissionService $rbacPermissionService;

    /**
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService
     */
    public function __construct(?RbacPermissionService $rbacPermissionService = null)
    {
        $this->rbacPermissionService = $rbacPermissionService ?? new RbacPermissionService();
    }

    /**
     * Return total number of opponents for index page summary.
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->getOpponentsTable()->find()->count();
    }

    /**
     * Build DataTables server-side payload.
     *
     * @param array<string,mixed> $params
     * @param mixed $identity Current authenticated identity
     * @return array{draw:int,total:int,filtered:int,data:array<int,array<string,mixed>>}
     */
    public function buildDataTablesResponse(array $params, mixed $identity = null): array
    {
        $opponentsTable = $this->getOpponentsTable();
        $schema = $opponentsTable->getSchema();
        $hasShortColumn = $schema->hasColumn('opponent_short');
        $hasAbbrColumn = $schema->hasColumn('opponent_abbr');
        $hasMascotColumn = $schema->hasColumn('opponent_mascot');

        $draw = (int)($params['draw'] ?? 1);
        $start = max(0, (int)($params['start'] ?? 0));
        $length = (int)($params['length'] ?? 50);
        if ($length < 1) {
            $length = 50;
        }
        $length = min($length, 500);
        $searchValue = trim((string)($params['searchValue'] ?? ''));

        $orderDir = strtolower((string)($params['orderDir'] ?? 'asc'));
        if (!in_array($orderDir, ['asc', 'desc'], true)) {
            $orderDir = 'asc';
        }

        $orderColumn = (int)($params['orderColumn'] ?? 0);
        $orderMap = [
            0 => 'Opponents.opponent_name',
            1 => $hasShortColumn ? 'Opponents.opponent_short' : 'Opponents.opponent_name',
            2 => $hasAbbrColumn ? 'Opponents.opponent_abbr' : 'Opponents.opponent_name',
            3 => 'Places.place_city',
        ];
        $orderField = $orderMap[$orderColumn] ?? 'Opponents.opponent_name';

        $total = $opponentsTable->find()->count();

        $selectFields = [
            'Opponents.id',
            'Opponents.opponent_name',
            'Opponents.place_id',
            'place_city' => 'Places.place_city',
            'place_state' => 'Places.place_state',
        ];
        if ($hasShortColumn) {
            $selectFields[] = 'Opponents.opponent_short';
        }
        if ($hasAbbrColumn) {
            $selectFields[] = 'Opponents.opponent_abbr';
        }
        if ($hasMascotColumn) {
            $selectFields[] = 'Opponents.opponent_mascot';
        }

        $searchOr = [
            'Opponents.opponent_name LIKE' => '%' . $searchValue . '%',
            'Places.place_city LIKE' => '%' . $searchValue . '%',
            'Places.place_state LIKE' => '%' . $searchValue . '%',
        ];
        if ($hasShortColumn) {
            $searchOr['Opponents.opponent_short LIKE'] = '%' . $searchValue . '%';
        }
        if ($hasAbbrColumn) {
            $searchOr['Opponents.opponent_abbr LIKE'] = '%' . $searchValue . '%';
        }
        if ($hasMascotColumn) {
            $searchOr['Opponents.opponent_mascot LIKE'] = '%' . $searchValue . '%';
        }

        $query = $opponentsTable->find()
            ->select($selectFields)
            ->leftJoinWith('Places');

        if ($searchValue !== '') {
            $query->where([
                'OR' => $searchOr,
            ]);
        }

        if ($orderDir === 'desc') {
            $query->orderByDesc($orderField);
        } else {
            $query->orderByAsc($orderField);
        }

        $filtered = $query->count();
        /** @var array<\App\Model\Entity\Opponent> $opponents */
        $opponents = $query->limit($length)->offset($start)->all()->toArray();

        $canUpdateOpponents = $this->rbacPermissionService->can($identity, 'Opponents', 'update');

        $data = [];
        foreach ($opponents as $opponent) {
            $editUrl = Router::url([
                'prefix' => 'Admin',
                'controller' => 'Opponents',
                'action' => 'edit',
                $opponent->id,
            ]);

            $placeCity = (string)($opponent->place_city ?? '');
            $placeState = (string)($opponent->place_state ?? '');
            $placeLabel = trim($placeCity . ($placeState !== '' ? ', ' . $placeState : ''));
            $actions = $canUpdateOpponents
                ? '<a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a>'
                : '<span class="text-muted">No actions</span>';

            $data[] = [
                'id' => (int)$opponent->id,
                'name' => h($opponent->opponent_name ?? ''),
                'short' => h($hasShortColumn ? ($opponent->opponent_short ?? '') : ''),
                'abbr' => h($hasAbbrColumn ? ($opponent->opponent_abbr ?? '') : ''),
                'place' => h($placeLabel !== '' ? $placeLabel : '-'),
                'actions' => $actions,
                'DT_RowId' => 'opponent-row-' . $opponent->id,
            ];
        }

        return compact('draw', 'total', 'filtered', 'data');
    }

    /**
     * Return index page data.
     *
     * @return array{opponents:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $opponents = $this->getOpponentsTable()->find()
            ->contain(['Places'])
            ->all();

        return compact('opponents');
    }

    /**
     * Return add form data.
     *
     * @return array{opponent:\App\Model\Entity\Opponent,places:array<int,string>,opponentsList:\Cake\Datasource\ResultSetInterface}
     */
    public function getAddFormData(): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->newEmptyEntity();
        $places = (new PlaceService())->getPlacesList();
        $opponentsList = $this->getOpponentsTable()->find('list')
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->all();

        return compact('opponent', 'places', 'opponentsList');
    }

    /**
     * Save new opponent.
     *
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,opponent:\App\Model\Entity\Opponent}
     */
    public function saveNewOpponent(array $data): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->newEmptyEntity();
        $opponent = $this->getOpponentsTable()->patchEntity($opponent, $data);
        $success = (bool)$this->getOpponentsTable()->save($opponent);

        return compact('success', 'opponent');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Opponent identifier
     * @return array{opponent:\App\Model\Entity\Opponent,places:array<int,string>,opponentsList:\Cake\Datasource\ResultSetInterface}
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->get($id);
        $places = (new PlaceService())->getPlacesList();
        $opponentsList = $this->getOpponentsTable()->find('list')
            ->where(['Opponents.id !=' => $id])
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->all();

        return compact('opponent', 'places', 'opponentsList');
    }

    /**
     * Save existing opponent.
     *
     * @param string|int $id Opponent identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,opponent:\App\Model\Entity\Opponent}
     */
    public function saveExistingOpponent(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->get($id);
        $opponent = $this->getOpponentsTable()->patchEntity($opponent, $data);
        $success = (bool)$this->getOpponentsTable()->save($opponent);

        return compact('success', 'opponent');
    }

    /**
     * Delete an opponent.
     *
     * @param string|int $id Opponent identifier
     * @param mixed $identity Current authenticated identity
     * @return bool
     */
    public function deleteOpponent(int|string $id, mixed $identity = null): bool
    {
        $scoped = $this->rbacPermissionService->scopeQuery(
            $identity,
            'Opponents',
            $this->getOpponentsTable()->find(),
            'delete',
            'id',
        );
        $opponent = $scoped->where(['Opponents.id' => (int)$id])->first();
        if ($opponent === null) {
            return false;
        }

        return (bool)$this->getOpponentsTable()->delete($opponent);
    }

    /**
     * Return JSON-ready search response payload.
     *
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array{success:bool,results:array<int,array<string,mixed>>}
     */
    public function buildSearchResponse(string $query, int $limit = 30): array
    {
        $query = trim($query);
        if ($query === '') {
            return [
                'success' => true,
                'results' => [],
            ];
        }

        $opponents = (new OpponentService())->searchOpponents($query, $limit);
        $results = [];
        foreach ($opponents as $opponent) {
            $results[] = [
                'id' => $opponent->id,
                'opponent_name' => $opponent->opponent_name,
                'opponent_short' => $opponent->opponent_short,
                'opponent_abbr' => $opponent->opponent_abbr,
                'opponent_mascot' => $opponent->opponent_mascot,
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Save a new opponent via popup form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @return array<string,mixed>
     */
    public function createOpponentFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->getOpponentsTable()->newEmptyEntity();
        $opponent = $this->getOpponentsTable()->patchEntity($opponent, $data);

        if ($this->getOpponentsTable()->save($opponent)) {
            return [
                'success' => true,
                'message' => 'The opponent has been saved.',
                'newOption' => [
                    'value' => $opponent->id,
                    'text' => $opponent->opponent_name,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($opponent) ?: ['Unable to save opponent.'],
        ];
    }

    /**
     * Convert validation errors to user-friendly strings.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity with validation errors
     * @return array<int,string>
     */
    private function collectValidationErrors(EntityInterface $entity): array
    {
        $errors = [];
        foreach ($entity->getErrors() as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errors[] = ucfirst((string)$field) . ': ' . (string)$error;
            }
        }

        return $errors;
    }

    /**
     * @return \App\Model\Table\OpponentsTable
     */
    private function getOpponentsTable(): OpponentsTable
    {
        /** @var \App\Model\Table\OpponentsTable $table */
        $table = TableRegistry::getTableLocator()->get('Opponents');

        return $table;
    }
}
