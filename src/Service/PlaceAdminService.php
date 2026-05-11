<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Place;
use App\Model\Table\PlacesTable;
use App\Model\Table\SitesTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * PlaceAdminService
 *
 * Owns administrative place management orchestration: standard CRUD flows,
 * edit-page site support data, duplicate-safe popup creation, and AJAX search
 * payload shaping.
 *
 * Notes:
 * - Preserve duplicate handling semantics for popup and add form flows.
 * - Keep response keys stable for frontend integrations.
 * - Keep HTTP concerns in controllers.
 */
class PlaceAdminService
{
    /**
     * Return total number of places for index page summary.
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->getPlacesTable()->find()->count();
    }

    /**
     * Build DataTables server-side payload.
     *
     * @param array<string,mixed> $params
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

        $orderColumn = (int)($params['orderColumn'] ?? 1);
        $orderMap = [
            0 => 'Places.place_country',
            1 => 'Places.place_city',
            2 => 'Places.place_state',
        ];
        $orderField = $orderMap[$orderColumn] ?? 'Places.place_city';

        $total = $this->getPlacesTable()->find()->count();

        $query = $this->getPlacesTable()->find()
            ->select(['id', 'place_country', 'place_city', 'place_state']);

        if ($searchValue !== '') {
            $query->where([
                'OR' => [
                    'Places.place_country LIKE' => '%' . $searchValue . '%',
                    'Places.place_city LIKE' => '%' . $searchValue . '%',
                    'Places.place_state LIKE' => '%' . $searchValue . '%',
                ],
            ]);
        }

        if ($orderDir === 'desc') {
            $query->orderByDesc($orderField);
        } else {
            $query->orderByAsc($orderField);
        }

        $filtered = $query->count();
        /** @var array<\App\Model\Entity\Place> $places */
        $places = $query->limit($length)->offset($start)->all()->toArray();

        $data = [];
        foreach ($places as $place) {
            $editUrl = Router::url([
                'prefix' => 'Admin',
                'controller' => 'Places',
                'action' => 'edit',
                $place->id,
            ]);

            $data[] = [
                'id' => (int)$place->id,
                'country' => h($place->place_country ?? ''),
                'city' => h($place->place_city ?? ''),
                'state' => h($place->place_state ?? ''),
                'actions' => '<a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a>',
                'DT_RowId' => 'place-row-' . $place->id,
            ];
        }

        return compact('draw', 'total', 'filtered', 'data');
    }

    /**
     * Return index page data.
     *
     * @return array{places:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $places = $this->getPlacesTable()->find()->all();

        return compact('places');
    }

    /**
     * Return add form data.
     *
     * @return array{place:\App\Model\Entity\Place}
     */
    public function getAddFormData(): array
    {
        /** @var \App\Model\Entity\Place $place */
        $place = $this->getPlacesTable()->newEmptyEntity();

        return compact('place');
    }

    /**
     * Save new place.
     *
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,place:\App\Model\Entity\Place,duplicateViolation:bool}
     */
    public function saveNewPlace(array $data): array
    {
        /** @var \App\Model\Entity\Place $place */
        $place = $this->getPlacesTable()->newEmptyEntity();
        $place = $this->getPlacesTable()->patchEntity($place, $data);
        $success = (bool)$this->getPlacesTable()->save($place);
        $errors = $place->getErrors();
        $duplicateViolation = isset($errors['place_country']['_isUnique']) || isset($errors['place_city']['_isUnique']);

        return compact('success', 'place', 'duplicateViolation');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Place identifier
     * @return array{place:\App\Model\Entity\Place,sites:\Cake\Datasource\ResultSetInterface,newSite:\App\Model\Entity\Site}
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\Place $place */
        $place = $this->getPlacesTable()->get($id);
        $sites = $this->getSitesTable()->find()->where(['place_id' => $id])->all();
        /** @var \App\Model\Entity\Site $newSite */
        $newSite = $this->getSitesTable()->newEmptyEntity();

        return compact('place', 'sites', 'newSite');
    }

    /**
     * Save existing place.
     *
     * @param string|int $id Place identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,place:\App\Model\Entity\Place,duplicateViolation:bool}
     */
    public function saveExistingPlace(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\Place $place */
        $place = $this->getPlacesTable()->get($id);
        $place = $this->getPlacesTable()->patchEntity($place, $data);
        $success = (bool)$this->getPlacesTable()->save($place);
        $errors = $place->getErrors();
        $duplicateViolation = isset($errors['place_country']['_isUnique']) || isset($errors['place_city']['_isUnique']);

        return compact('success', 'place', 'duplicateViolation');
    }

    /**
     * Delete a place.
     *
     * @param string|int $id Place identifier
     * @return bool
     */
    public function deletePlace(int|string $id): bool
    {
        $place = $this->getPlacesTable()->get($id);

        return (bool)$this->getPlacesTable()->delete($place);
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

        $places = (new PlaceService())->searchPlaces($query, $limit);
        $results = [];
        foreach ($places as $place) {
            $results[] = [
                'id' => $place->id,
                'place_country' => $place->place_country,
                'place_city' => $place->place_city,
                'place_state' => $place->place_state,
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Save a place via popup form with duplicate handling.
     *
     * @param array<string,mixed> $data Request payload
     * @return array<string,mixed>
     */
    public function createPlaceFromPopup(array $data): array
    {
        $conditions = [
            'place_country' => $data['place_country'] ?? '',
            'place_city' => $data['place_city'] ?? '',
            'place_state' => $data['place_state'] ?? '',
        ];
        /** @var \App\Model\Entity\Place|null $existing */
        $existing = $this->getPlacesTable()->find()->where($conditions)->first();

        if ($existing) {
            return [
                'success' => true,
                'message' => 'Place already exists — selected automatically.',
                'newOption' => [
                    'value' => $existing->id,
                    'text' => $this->buildPlaceLabel($existing),
                ],
                'place' => [
                    'id' => $existing->id,
                    'place_country' => $existing->place_country,
                    'place_city' => $existing->place_city,
                    'place_state' => $existing->place_state,
                ],
            ];
        }

        /** @var \App\Model\Entity\Place $place */
        $place = $this->getPlacesTable()->newEmptyEntity();
        $place = $this->getPlacesTable()->patchEntity($place, $data);
        if ($this->getPlacesTable()->save($place)) {
            return [
                'success' => true,
                'message' => 'The place has been saved.',
                'newOption' => [
                    'value' => $place->id,
                    'text' => $this->buildPlaceLabel($place),
                ],
                'place' => [
                    'id' => $place->id,
                    'place_country' => $place->place_country,
                    'place_city' => $place->place_city,
                    'place_state' => $place->place_state,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($place) ?: ['Unable to save place.'],
        ];
    }

    /**
     * Build display label for a place option.
     *
     * @param \App\Model\Entity\Place $place Place entity
     * @return string
     */
    private function buildPlaceLabel(Place $place): string
    {
        $label = (string)$place->place_city;
        if (!empty($place->place_state)) {
            $label .= ', ' . $place->place_state;
        }

        return $label;
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
     * @return \App\Model\Table\PlacesTable
     */
    private function getPlacesTable(): PlacesTable
    {
        /** @var \App\Model\Table\PlacesTable $table */
        $table = TableRegistry::getTableLocator()->get('Places');

        return $table;
    }

    /**
     * @return \App\Model\Table\SitesTable
     */
    private function getSitesTable(): SitesTable
    {
        /** @var \App\Model\Table\SitesTable $table */
        $table = TableRegistry::getTableLocator()->get('Sites');

        return $table;
    }
}
