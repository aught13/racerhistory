<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Persons Controller
 *
 * Provides CRUD, bulk, and AJAX operations for managing persons (people records).
 * Mirrors patterns used in SeasonsController and SportsController for consistency.
 *
 * Persons represent individual people (athletes, coaches, etc.) with name parts and
 * optional birth/death dates and an image reference.
 *
 * @property \App\Model\Table\PersonsTable $Persons
 */
class PersonsController extends AppController
{
    /**
     * @var \App\Service\StatsService Service for sport-specific statistics
     */
    protected \App\Service\StatsService $Stats;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadService('Stats');
    }

    /**
     * Index: list persons (shell only — data loaded via datatables action).
     *
     * @return void
     */
    public function index(): void
    {
        $total = $this->Persons->find()->count();
        $this->set('personCount', $total);
    }

    /**
     * DataTables server-side JSON endpoint for the persons index.
     *
     * @return \Cake\Http\Response
     */
    public function datatables(): Response
    {
        $this->request->allowMethod(['get']);

        $draw = (int)$this->request->getQuery('draw');
        $start = max(0, (int)$this->request->getQuery('start'));
        $length = (int)$this->request->getQuery('length');
        if ($length < 1) {
            $length = 50;
        }
        $length = min($length, 500);

        $searchValue = trim((string)($this->request->getQuery('search')['value'] ?? ''));

        $total = $this->Persons->find()->count();

        $query = $this->Persons->find()
            ->select(['id', 'first', 'last', 'full', 'display', 'birth']);

        // Apply DataTables ordering
        $order = $this->request->getQuery('order');
        $direction = 'asc';
        if (is_array($order) && !empty($order)) {
            $firstOrder = reset($order);
            if (is_array($firstOrder)) {
                $dir = strtolower((string)($firstOrder['dir'] ?? 'asc'));
                if (in_array($dir, ['asc', 'desc'], true)) {
                    $direction = $dir;
                }
            }
        }
        if ($direction === 'desc') {
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
            $viewUrl = $this->getRequest()->getAttribute('base') . '/admin/persons/view/' . $person->id;
            $editUrl = $this->getRequest()->getAttribute('base') . '/admin/persons/edit/' . $person->id;
            $deleteUrl = $this->getRequest()->getAttribute('base') . '/admin/persons/delete/' . $person->id;

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

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]));
    }

    /**
     * View a single person.
     *
     * @param string $id Person id
     * @return void
     */
    public function view(string $id): void
    {
        $person = $this->Persons->get($id, contain: [
            'TeamSeasonRosters' => [
                'TeamSeasons' => ['Teams' => ['Sports'], 'Seasons'],
            ],
        ]);
        assert($person instanceof \App\Model\Entity\Person);

        // Organize roster entries by sport and calculate career stats
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

            // Fetch stats only if this sport has service support
            $gameStats = [];
            $seasonStats = null;
            if ($this->Stats->hasSportSupport($sportId)) {
                $seasonStats = $this->Stats->getPersonSeasonStats($sportId, (int)$roster->id);
                $gameStats = $this->Stats->getPersonGameStats($sportId, (int)$roster->id);
            }

            $rostersBySport[$sportId]['rosters'][] = [
                'roster' => $roster,
                'teamSeason' => $teamSeason,
                'gameStats' => $gameStats,
                'seasonStats' => $seasonStats,
            ];

            // Calculate career totals for sports with statistical support
            if ($this->Stats->hasSportSupport($sportId)) {
                if (!isset($careerStatsBySport[$sportId])) {
                    $careerStatsBySport[$sportId] = [
                        'sport' => $sport,
                        'totals' => $this->Stats->initializeStats($sportId, 'player'),
                        'seasons' => [],
                        'minYear' => null,
                        'maxYear' => null,
                    ];
                }

                if ($seasonStats) {
                    // Store individual season stats
                    $careerStatsBySport[$sportId]['seasons'][] = [
                        'teamSeason' => $teamSeason,
                        'stats' => $seasonStats,
                    ];

                    // Track year range
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

                    // Add season stats to career totals
                    $this->Stats->addSeasonStats($sportId, $careerStatsBySport[$sportId]['totals'], $seasonStats);
                }
            }
        }

        $this->set(compact('person', 'rostersBySport', 'careerStatsBySport'));
    }

    /**
     * Add person form & processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        /** @var \App\Model\Entity\Person $person */
        $person = $this->Persons->newEmptyEntity();
        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Handle person_image as direct image ID
            $personImage = $data['person_image'] ?? null;
            if (is_numeric($personImage)) {
                $data['person_image'] = (int)$personImage;
            }

            /** @var \App\Model\Entity\Person $person */
            $person = $this->Persons->patchEntity($person, $data);
            if ($this->Persons->save($person)) {
                $this->Flash->success(__('The person has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The person could not be saved. Please, try again.'));
        }
        $this->set(compact('person'));

        return null;
    }

    /**
     * Edit person form & processing.
     *
     * @param string $id Person id
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id): ?Response
    {
        $person = $this->Persons->get($id, contain: ['BirthPlace']);
        if ($this->request->is(['patch','post','put'])) {
            $data = $this->request->getData();

            // Handle person_image as direct image ID
            $personImage = $data['person_image'] ?? null;
            if (is_numeric($personImage)) {
                $data['person_image'] = (int)$personImage;
            }

            $person = $this->Persons->patchEntity($person, $data);
            if ($this->Persons->save($person)) {
                $this->Flash->success(__('The person has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The person could not be saved. Please, try again.'));
        }
        $this->set(compact('person'));

        return null;
    }

    /**
     * Delete a person.
     *
     * @param string $id Person id
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post','delete']);
        $person = $this->Persons->get($id);
        if ($this->Persons->delete($person)) {
            $this->Flash->success(__('The person has been deleted.'));
        } else {
            $this->Flash->error(__('The person could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete persons.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $ids = (array)$this->request->getData('person_ids');
        $ids = array_values(array_filter($ids, fn($v) => $v !== '' && $v !== null && ctype_digit((string)$v)));
        if (empty($ids)) {
            $this->Flash->error('No persons selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $entity = $this->Persons->get($id);
                if ($this->Persons->delete($entity)) {
                    $deleted++;
                }
            } catch (RecordNotFoundException $e) {
                continue;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} person(s).', $deleted));
        } else {
            $this->Flash->error('No persons could be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk dispatcher.
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $action = $this->request->getData('bulk_action');
        if ($action === 'delete') {
            return $this->bulkDelete();
        }
        $this->Flash->error('Invalid bulk action.');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX add (popup form) endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        /** @var \App\Model\Entity\Person $person */
        $person = $this->Persons->newEmptyEntity();
        if ($this->request->is('post')) {
            /** @var \App\Model\Entity\Person $person */
            $person = $this->Persons->patchEntity($person, $this->request->getData());
            if ($this->Persons->save($person)) {
                // Use public method for label
                $label = $person->getLabel();
                $personId = (int)($person->get('id'));
                $response = [
                    'success' => true,
                    'message' => __('The person has been saved.'),
                    'newOption' => [
                        'value' => $personId,
                        'text' => $label,
                    ],
                ];
            } else {
                $errors = [];
                foreach ($person->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errors[] = ucfirst($field) . ': ' . $error;
                    }
                }
                $response = [
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save person. Please try again.'],
                ];
            }
        } else {
            $response = [
                'success' => false,
                'errors' => ['Invalid request method.'],
            ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($response));
    }

    /**
     * AJAX search persons for dynamic select (debounced client queries).
     * Accepts query param 'q'. Returns limited JSON list of id/text pairs.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q'));
        $query = $this->Persons->find();
        if ($q !== '') {
            $like = '%' . str_replace(['%','_'], ['\\%','\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'Persons.display LIKE' => $like,
                    'Persons.first LIKE' => $like,
                    'Persons.last LIKE' => $like,
                    'Persons.full LIKE' => $like,
                ],
            ]);
        }
        $query->select(['id','display','first','last'])
            ->orderByAsc('display')
            ->limit(30);

        $results = [];
        /** @var \App\Model\Entity\Person $p */
        foreach ($query as $p) {
            $label = $p->getLabel();
            $results[] = [ 'value' => $p->id, 'text' => $label ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'results' => $results,
            ]));
    }
}
