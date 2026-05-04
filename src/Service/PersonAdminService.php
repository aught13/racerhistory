<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Person;
use App\Model\Table\PersonsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * PersonAdminService
 *
 * Owns the full administrative slice for Person records. Used exclusively by
 * Admin/PersonsController to keep that controller free of ORM queries and
 * business logic.
 *
 * Responsibilities:
 * - Return total person count for the index shell.
 * - Build the DataTables server-side JSON response (pagination, search,
 *   sort, action-button HTML).
 * - Assemble the view-action data: person with rosters, sport-grouped career
 *   stats via StatsService.
 * - Process add/edit/delete/bulk-delete form submissions.
 * - Handle the ajax popup-add flow and the ajax search endpoint.
 *
 * Notes:
 * - Keep HTTP concerns (Flash, redirect, allowMethod, withType) in the
 *   controller.
 * - Returned array keys and JSON response shapes are relied on by tests and
 *   the frontend — do not rename them without updating call sites.
 * - The datatables action HTML is generated here using Router::url() so the
 *   service stays independent of request attributes.
 *
 * @property \App\Model\Table\PersonsTable $personsTable
 * @property \App\Service\StatsService $statsService
 */
class PersonAdminService
{
    /**
     * @var \App\Model\Table\PersonsTable
     */
    private PersonsTable $personsTable;

    /**
     * @var \App\Service\StatsService
     */
    private StatsService $statsService;

    /**
     * @param \App\Model\Table\PersonsTable|null $personsTable
     * @param \App\Service\StatsService|null $statsService
     */
    public function __construct(
        ?PersonsTable $personsTable = null,
        ?StatsService $statsService = null,
    ) {
        /** @var \App\Model\Table\PersonsTable $table */
        $table = $personsTable ?? TableRegistry::getTableLocator()->get('Persons');
        $this->personsTable = $table;

        $this->statsService = $statsService ?? new StatsService();
    }

    /**
     * Return total number of persons for the index count label.
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->personsTable->find()->count();
    }

    /**
     * Build the DataTables server-side JSON payload.
     *
     * Accepted keys in $params:
     *  - draw (int)
     *  - start (int)
     *  - length (int, capped at 500)
     *  - searchValue (string)
     *  - orderDir ('asc'|'desc', default 'asc')
     *
     * @param array<string,mixed> $params DataTables request parameters
     * @return array{draw:int,total:int,filtered:int,data:array<int,array<string,mixed>>}
     */
    public function buildDataTablesResponse(array $params): array
    {
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

        $total = $this->personsTable->find()->count();

        $query = $this->personsTable->find()
            ->select(['id', 'first', 'last', 'full', 'display', 'birth']);

        if ($orderDir === 'desc') {
            $query->orderByDesc('Persons.last')->orderByDesc('Persons.first');
        } else {
            $query->orderByAsc('Persons.last')->orderByAsc('Persons.first');
        }

        if ($searchValue !== '') {
            $query->where([
                'OR' => [
                    'Persons.first LIKE' => '%' . $searchValue . '%',
                    'Persons.last LIKE' => '%' . $searchValue . '%',
                    'Persons.full LIKE' => '%' . $searchValue . '%',
                    'Persons.display LIKE' => '%' . $searchValue . '%',
                ],
            ]);
        }

        $filtered = $query->count();
        /** @var array<\App\Model\Entity\Person> $persons */
        $persons = $query->limit($length)->offset($start)->all()->toArray();

        $data = [];
        foreach ($persons as $person) {
            $displayName = h($person->display ?? trim($person->first . ' ' . $person->last));
            $viewUrl = Router::url([
                'prefix' => 'Admin',
                'controller' => 'Persons',
                'action' => 'view',
                $person->id,
            ]);
            $editUrl = Router::url([
                'prefix' => 'Admin',
                'controller' => 'Persons',
                'action' => 'edit',
                $person->id,
            ]);
            $deleteUrl = Router::url([
                'prefix' => 'Admin',
                'controller' => 'Persons',
                'action' => 'delete',
                $person->id,
            ]);

            $actions = '<a href="' . $viewUrl . '" class="btn btn-sm btn-info">View</a> ' .
                '<a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a> ' .
                '<button type="button" class="btn btn-sm btn-danger" ' .
                    'data-bs-toggle="modal" data-bs-target="#confirm-delete-modal" ' .
                    'data-delete-url="' . $deleteUrl . '" ' .
                    'data-edit-url="' . $editUrl . '" ' .
                    'data-item-type="person">Delete</button>';

            $data[] = [
                'id' => $person->id,
                'display' => $displayName,
                'first' => h($person->first ?? ''),
                'last' => h($person->last ?? ''),
                'birth' => h($person->birth ?? ''),
                'actions' => $actions,
                'DT_RowId' => 'person-row-' . $person->id,
            ];
        }

        return compact('draw', 'total', 'filtered', 'data');
    }

    /**
     * Assemble view-action data for a person.
     *
     * Returns roster entries grouped by sport with per-sport career stats
     * calculated via StatsService when the sport has statistical support.
     *
     * @param string $id Person ID
     * @return array{person:\App\Model\Entity\Person,rostersBySport:array<mixed>,careerStatsBySport:array<mixed>}
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getViewData(string $id): array
    {
        /** @var \App\Model\Entity\Person $person */
        $person = $this->personsTable->get($id, contain: [
            'TeamSeasonRosters' => [
                'TeamSeasons' => ['Teams' => ['Sports'], 'Seasons'],
            ],
        ]);

        $rostersBySport = [];
        $careerStatsBySport = [];

        foreach ($person->team_season_rosters as $roster) {
            $teamSeason = $roster->team_season;
            $sport = $teamSeason->team->sport ?? null;
            if (!$sport) {
                continue;
            }

            $sportId = $sport->id;

            if (!isset($rostersBySport[$sportId])) {
                $rostersBySport[$sportId] = [
                    'sport' => $sport,
                    'rosters' => [],
                ];
            }

            $gameStats = [];
            $seasonStats = null;
            if ($this->statsService->hasSportSupport($sportId)) {
                $seasonStats = $this->statsService->getPersonSeasonStats($sportId, (int)$roster->id);
                $gameStats = $this->statsService->getPersonGameStats($sportId, (int)$roster->id);
            }

            $rostersBySport[$sportId]['rosters'][] = [
                'roster' => $roster,
                'teamSeason' => $teamSeason,
                'gameStats' => $gameStats,
                'seasonStats' => $seasonStats,
            ];

            if ($this->statsService->hasSportSupport($sportId)) {
                if (!isset($careerStatsBySport[$sportId])) {
                    $careerStatsBySport[$sportId] = [
                        'sport' => $sport,
                        'totals' => $this->statsService->initializeStats($sportId, 'player'),
                        'seasons' => [],
                        'minYear' => null,
                        'maxYear' => null,
                    ];
                }

                if ($seasonStats) {
                    $careerStatsBySport[$sportId]['seasons'][] = [
                        'teamSeason' => $teamSeason,
                        'stats' => $seasonStats,
                    ];

                    $startYear = $teamSeason->season->start ?? null;
                    $endYear = $teamSeason->season->end ?? null;
                    if ($startYear !== null) {
                        $minYear = $careerStatsBySport[$sportId]['minYear'];
                        if ($minYear === null || $startYear < $minYear) {
                            $careerStatsBySport[$sportId]['minYear'] = $startYear;
                        }
                    }
                    if ($endYear !== null) {
                        $maxYear = $careerStatsBySport[$sportId]['maxYear'];
                        if ($maxYear === null || $endYear > $maxYear) {
                            $careerStatsBySport[$sportId]['maxYear'] = $endYear;
                        }
                    }

                    $totals = &$careerStatsBySport[$sportId]['totals'];
                    $this->statsService->addSeasonStats($sportId, $totals, $seasonStats);
                }
            }
        }

        return compact('person', 'rostersBySport', 'careerStatsBySport');
    }

    /**
     * Retrieve an existing Person entity with BirthPlace for the edit form.
     *
     * @param string $id Person ID
     * @return \App\Model\Entity\Person
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getEditEntity(string $id): Person
    {
        /** @var \App\Model\Entity\Person $person */
        $person = $this->personsTable->get($id, contain: ['BirthPlace']);

        return $person;
    }

    /**
     * Process an add form submission.
     *
     * Normalises person_image to int when a numeric value is provided.
     *
     * @param array<string,mixed> $data Form data
     * @return array{success:bool,person:\App\Model\Entity\Person}
     */
    public function add(array $data): array
    {
        $data = $this->normalizePersonImage($data);
        /** @var \App\Model\Entity\Person $person */
        $person = $this->personsTable->newEmptyEntity();
        $person = $this->personsTable->patchEntity($person, $data);

        if ($this->personsTable->save($person)) {
            return ['success' => true, 'person' => $person];
        }

        return ['success' => false, 'person' => $person];
    }

    /**
     * Process an edit form submission.
     *
     * @param string $id Person ID
     * @param array<string,mixed> $data Form data
     * @return array{success:bool,person:\App\Model\Entity\Person}
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function edit(string $id, array $data): array
    {
        $data = $this->normalizePersonImage($data);
        /** @var \App\Model\Entity\Person $person */
        $person = $this->personsTable->get($id, contain: ['BirthPlace']);
        $person = $this->personsTable->patchEntity($person, $data);

        if ($this->personsTable->save($person)) {
            return ['success' => true, 'person' => $person];
        }

        return ['success' => false, 'person' => $person];
    }

    /**
     * Delete a single person.
     *
     * @param string $id Person ID
     * @return bool
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function delete(string $id): bool
    {
        $person = $this->personsTable->get($id);

        return (bool)$this->personsTable->delete($person);
    }

    /**
     * Delete multiple persons by ID, silently skipping missing records.
     *
     * @param array<int|string> $ids Sanitized numeric IDs
     * @return int Count of successfully deleted records
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $person = $this->personsTable->get($id);
                if ($this->personsTable->delete($person)) {
                    $deleted++;
                }
            } catch (RecordNotFoundException $e) {
                continue;
            }
        }

        return $deleted;
    }

    /**
     * Persist a person submitted via the ajax popup form.
     *
     * Returns a structured array compatible with the existing frontend
     * popup handler.
     *
     * @param array<string,mixed> $data Form data
     * @return array{success:bool,message?:string,newOption?:array{value:int,text:string},errors?:array<string>}
     */
    public function createPersonFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\Person $person */
        $person = $this->personsTable->newEmptyEntity();
        $person = $this->personsTable->patchEntity($person, $data);

        if ($this->personsTable->save($person)) {
            $label = $person->getLabel();

            return [
                'success' => true,
                'message' => __('The person has been saved.'),
                'newOption' => [
                    'value' => (int)$person->get('id'),
                    'text' => $label,
                ],
            ];
        }

        $errors = [];
        foreach ($person->getErrors() as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errors[] = ucfirst((string)$field) . ': ' . $error;
            }
        }

        return [
            'success' => false,
            'errors' => $errors ?: ['Unable to save person. Please try again.'],
        ];
    }

    /**
     * Search persons by name for the ajax autocomplete endpoint.
     *
     * Searches across display, first, last, and full fields.
     * Returns a list of {value, text} pairs ordered by display name.
     *
     * @param string $q Search query (pass empty string for no filtering)
     * @param int $limit Maximum results
     * @return array{success:bool,results:array<int,array{value:int,text:string}>}
     */
    public function buildAjaxSearchResponse(string $q, int $limit = 30): array
    {
        $query = $this->personsTable->find()
            ->select(['id', 'display', 'first', 'last'])
            ->orderByAsc('display')
            ->limit($limit);

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'Persons.display LIKE' => $like,
                    'Persons.first LIKE' => $like,
                    'Persons.last LIKE' => $like,
                    'Persons.full LIKE' => $like,
                ],
            ]);
        }

        $results = [];
        /** @var \App\Model\Entity\Person $p */
        foreach ($query as $p) {
            $results[] = [
                'value' => $p->id,
                'text' => $p->getLabel(),
            ];
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Normalise the person_image field to int when a numeric value is supplied.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizePersonImage(array $data): array
    {
        $personImage = $data['person_image'] ?? null;
        if (is_numeric($personImage)) {
            $data['person_image'] = (int)$personImage;
        }

        return $data;
    }
}
