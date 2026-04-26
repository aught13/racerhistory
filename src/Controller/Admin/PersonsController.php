<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Persons Controller
 *
 * Provides CRUD operations for managing persons in the admin interface. The index action lists all persons, while the add and edit actions allow for creating and updating persons, respectively. The delete action handles person deletion, with a bulk delete option for multiple records. The controller also includes AJAX endpoints for adding new persons from a popup form and searching persons for dynamic select inputs, returning JSON responses for seamless integration with the frontend.
 *
 * Actions:
 * - index: Lists all persons with a count of total records. The actual data is loaded via the datatables action for server-side processing.
 * - datatables: Provides a JSON endpoint for DataTables server-side processing, including pagination, searching, and ordering.
 * - view: Displays detailed information about a specific person, including their roster entries organized by sport and career stats calculated using the StatsService. Throws RecordNotFoundException if the person does not exist.
 * - add: Handles the creation of a new person, including form display and processing. Validates input data and provides feedback via flash messages.
 * - edit: Handles the editing of an existing person, including form display and processing. Validates input data and provides feedback via flash messages. Throws RecordNotFoundException if the person does not exist.
 * - delete: Handles the deletion of a person, ensuring that the request method is POST or DELETE to prevent accidental deletions via GET requests. Throws RecordNotFoundException if the person does not exist.
 * - bulkDelete: Handles the deletion of multiple persons based on an array of IDs, ensuring that the request method is POST to prevent accidental deletions via GET requests. Validates the input IDs and provides feedback on the number of records deleted.
 * - bulk: A dispatcher for bulk actions, currently supporting bulk deletion of persons. Validates the requested action and redirects accordingly.
 * - ajaxAdd: Provides an endpoint for adding a new person from a popup form, returning success or error messages in JSON format for seamless integration with the frontend. This allows administrators to quickly add new persons without needing to navigate away from their current context. The form data is validated and any errors are returned in a structured format to help guide the user in correcting any issues with their input.
 * - ajaxSearch: Provides an endpoint for searching persons based on a query string, returning results in JSON format for use in autocomplete fields or similar UI components. The search looks for matches in the display, first, last, and full name fields, and returns a limited set of results ordered by display name.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage persons. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete and bulk delete actions use POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 *
 * Dependencies:
 * - StatsService: Used to retrieve sport-specific statistics for a person when viewing their details.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations.
 *
 * Note: The view action organizes a person's roster entries by sport and calculates career stats using the StatsService, demonstrating how the controller can handle more complex data retrieval and processing while still keeping the core logic focused on request handling and response formatting. The AJAX endpoints provide a way to interact with the person data without full page reloads, enhancing the user experience in the admin interface.
 *
 * @property \App\Model\Table\PersonsTable $Persons
 * @property \App\Service\StatsService $Stats
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\PersonsTable $Persons
 * @property \App\Service\StatsService $Stats
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
