<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\PlacesTable;
use App\Model\Table\SitesTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * SiteAdminService
 *
 * Owns administrative site management orchestration including CRUD persistence,
 * place-list support data for forms, popup payload generation, and search
 * response shaping with optional place filtering.
 *
 * Notes:
 * - Preserve optional place filtering behavior for ajax-search.
 * - Keep popup response payload keys stable.
 * - Keep controller logic focused on request/response concerns.
 */
class SiteAdminService
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
     * Return total number of sites for index page summary.
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->getSitesTable()->find()->count();
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
        $sitesTable = $this->getSitesTable();
        $siteSchema = $sitesTable->getSchema();
        $hasCapacityColumn = $siteSchema->hasColumn('capacity');

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
            0 => 'Sites.site_name',
            1 => 'Places.place_city',
            2 => $hasCapacityColumn ? 'Sites.capacity' : 'Sites.site_name',
        ];
        $orderField = $orderMap[$orderColumn] ?? 'Sites.site_name';

        $total = $sitesTable->find()->count();

        $selectFields = [
            'Sites.id',
            'Sites.site_name',
            'Sites.place_id',
            'place_city' => 'Places.place_city',
            'place_state' => 'Places.place_state',
        ];
        if ($hasCapacityColumn) {
            $selectFields[] = 'Sites.capacity';
        }

        $query = $sitesTable->find()
            ->select($selectFields)
            ->leftJoinWith('Places');

        if ($searchValue !== '') {
            $query->where([
                'OR' => [
                    'Sites.site_name LIKE' => '%' . $searchValue . '%',
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
        /** @var array<\App\Model\Entity\Site> $sites */
        $sites = $query->limit($length)->offset($start)->all()->toArray();

        $canUpdateSites = $this->rbacPermissionService->can($identity, 'Sites', 'update');

        $data = [];
        foreach ($sites as $site) {
            $editUrl = Router::url([
                'prefix' => 'Admin',
                'controller' => 'Sites',
                'action' => 'edit',
                $site->id,
            ]);
            $placeCity = (string)($site->place_city ?? '');
            $placeState = (string)($site->place_state ?? '');
            $placeLabel = trim($placeCity . ($placeState !== '' ? ', ' . $placeState : ''));

            $data[] = [
                'id' => (int)$site->id,
                'name' => h($site->site_name ?? ''),
                'place' => h($placeLabel !== '' ? $placeLabel : '-'),
                'capacity' => h((string)($hasCapacityColumn ? ($site->capacity ?? '') : '')),
                'actions' => $canUpdateSites
                    ? '<a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a>'
                    : '<span class="text-muted">No actions</span>',
                'DT_RowId' => 'site-row-' . $site->id,
            ];
        }

        return compact('draw', 'total', 'filtered', 'data');
    }

    /**
     * Return index page data.
     *
     * @return array{sites:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $sites = $this->getSitesTable()->find()
            ->contain(['Places'])
            ->all();

        return compact('sites');
    }

    /**
     * Return add form data.
     *
     * @return array{site:\App\Model\Entity\Site,places:\Cake\Datasource\ResultSetInterface}
     */
    public function getAddFormData(): array
    {
        /** @var \App\Model\Entity\Site $site */
        $site = $this->getSitesTable()->newEmptyEntity();
        $places = $this->getPlacesTable()->find('list')->all();

        return compact('site', 'places');
    }

    /**
     * Save new site.
     *
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,site:\App\Model\Entity\Site}
     */
    public function saveNewSite(array $data): array
    {
        /** @var \App\Model\Entity\Site $site */
        $site = $this->getSitesTable()->newEmptyEntity();
        $site = $this->getSitesTable()->patchEntity($site, $data);
        $success = (bool)$this->getSitesTable()->save($site);

        return compact('success', 'site');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Site identifier
     * @return array{site:\App\Model\Entity\Site,places:\Cake\Datasource\ResultSetInterface}
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\Site $site */
        $site = $this->getSitesTable()->get($id);
        $places = $this->getPlacesTable()->find('list')->all();

        return compact('site', 'places');
    }

    /**
     * Save existing site.
     *
     * @param string|int $id Site identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,site:\App\Model\Entity\Site}
     */
    public function saveExistingSite(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\Site $site */
        $site = $this->getSitesTable()->get($id);
        $site = $this->getSitesTable()->patchEntity($site, $data);
        $success = (bool)$this->getSitesTable()->save($site);

        return compact('success', 'site');
    }

    /**
     * Delete a site.
     *
     * @param string|int $id Site identifier
     * @param mixed $identity Current authenticated identity
     * @return bool
     */
    public function deleteSite(int|string $id, mixed $identity = null): bool
    {
        $scoped = $this->rbacPermissionService->scopeQuery(
            $identity,
            'Sites',
            $this->getSitesTable()->find(),
            'delete',
            'id',
        );
        $site = $scoped->where(['Sites.id' => (int)$id])->first();
        if ($site === null) {
            return false;
        }

        return (bool)$this->getSitesTable()->delete($site);
    }

    /**
     * Return JSON-ready search response payload.
     *
     * @param string $query Search query
     * @param int|null $placeId Optional place filter
     * @param int $limit Result limit
     * @return array{success:bool,results:array<int,array<string,mixed>>}
     */
    public function buildSearchResponse(string $query, ?int $placeId = null, int $limit = 30): array
    {
        $query = trim($query);
        if ($query === '') {
            return [
                'success' => true,
                'results' => [],
            ];
        }

        $sites = (new SiteService())->searchSites($query, $limit);
        $results = [];
        foreach ($sites as $site) {
            if ($placeId && (int)$site->place_id !== $placeId) {
                continue;
            }

            $results[] = [
                'id' => $site->id,
                'site_name' => $site->site_name,
                'capacity' => $site->capacity,
                'place_city' => $site->place->place_city ?? '',
                'place_state' => $site->place->place_state ?? '',
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Save a site via popup form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @return array<string,mixed>
     */
    public function createSiteFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\Site $site */
        $site = $this->getSitesTable()->newEmptyEntity();
        $site = $this->getSitesTable()->patchEntity($site, $data);

        if ($this->getSitesTable()->save($site)) {
            $displayLabel = (new SiteService())->getDisplayLabel((int)$site->id);

            return [
                'success' => true,
                'message' => 'The site has been saved.',
                'newOption' => [
                    'value' => $site->id,
                    'text' => $displayLabel,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($site) ?: ['Unable to save site.'],
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
     * @return \App\Model\Table\SitesTable
     */
    private function getSitesTable(): SitesTable
    {
        /** @var \App\Model\Table\SitesTable $table */
        $table = TableRegistry::getTableLocator()->get('Sites');

        return $table;
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
}
