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
     * Index: list persons.
     *
     * @return void
     */
    public function index(): void
    {
        /** @var \Cake\ORM\ResultSet<\App\Model\Entity\Person> $persons */
        $persons = $this->Persons->find()->all();
        $this->set(compact('persons'));
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
                'StatBasketGamePerson' => ['Games'],
                'StatBasketSeasonPerson',
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

            // Aggregate game stats for this roster
            $gameStats = [];
            foreach ($roster->stat_basket_game_person as $gameStat) {
                $gameId = $gameStat->game_id;
                if (!isset($gameStats[$gameId])) {
                    $gameStats[$gameId] = [
                        'game' => $gameStat->game,
                        'stats' => [],
                    ];
                }
                $gameStats[$gameId]['stats'][] = $gameStat;
            }

            // Get season totals for this roster
            $seasonStats = !empty($roster->stat_basket_season_person) ? $roster->stat_basket_season_person[0] : null;

            $rostersBySport[$sportId]['rosters'][] = [
                'roster' => $roster,
                'teamSeason' => $teamSeason,
                'gameStats' => $gameStats,
                'seasonStats' => $seasonStats,
            ];

            // Calculate career totals for basketball (sport_id = 1)
            if ($sportId === 1 && $seasonStats) {
                if (!isset($careerStatsBySport[$sportId])) {
                    $careerStatsBySport[$sportId] = [
                        'sport' => $sport,
                        'totals' => $this->initializeBasketballStats(),
                    ];
                }

                // Add season stats to career totals
                $this->addBasketballStats($careerStatsBySport[$sportId]['totals'], $seasonStats);
            }
        }

        $this->set(compact('person', 'rostersBySport', 'careerStatsBySport'));
    }

    /**
     * Initialize basketball stats array with zero values
     *
     * @return array<string, int> Stats array
     */
    private function initializeBasketballStats(): array
    {
        return [
            'GP' => 0, 'GS' => 0, 'MIN' => 0, 'FGM' => 0, 'FGA' => 0,
            'TPM' => 0, 'TPA' => 0, 'FTM' => 0, 'FTA' => 0,
            'ORB' => 0, 'DRB' => 0, 'RB' => 0, 'AST' => 0, 'STL' => 0,
            'BS' => 0, 'TRN' => 0, 'PF' => 0, 'TF' => 0, 'PTS' => 0,
        ];
    }

    /**
     * Add basketball season stats to career totals
     *
     * @param array<string, int> $totals Career totals array (modified by reference)
     * @param \App\Model\Entity\StatBasketSeasonPerson $seasonStats Season stats entity
     * @return void
     */
    private function addBasketballStats(array &$totals, \App\Model\Entity\StatBasketSeasonPerson $seasonStats): void
    {
        $fields = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
                   'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF', 'PTS'];

        foreach ($fields as $field) {
            $value = $seasonStats->$field ?? 0;
            $totals[$field] += is_numeric($value) ? (int)$value : 0;
        }
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
        $person = $this->Persons->get($id);
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
