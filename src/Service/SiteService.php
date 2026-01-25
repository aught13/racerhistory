<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * SiteService
 *
 * Service layer for Site entity CRUD and business logic.
 */
class SiteService
{
    /**
     * Get a site by ID.
     *
     * @param int $siteId Site ID
     * @return \App\Model\Entity\Site|null
     */
    public function getSiteById(int $siteId): ?\App\Model\Entity\Site
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');

        return $sites->find()
            ->contain(['Places'])
            ->where(['Sites.id' => $siteId])
            ->first();
    }

    /**
     * Get a friendly display label for a site.
     *
     * @param int $siteId Site ID
     * @return string
     */
    public function getDisplayLabel(int $siteId): string
    {
        $site = $this->getSiteById($siteId);
        if (!$site) {
            return 'Site #' . $siteId;
        }

        $parts = array_filter([
            $site->place->place_name ?? null,
            $site->place->place_state ?? null,
            $site->site_name ?? null,
        ]);
        if ($parts) {
            return implode(', ', $parts);
        }

        return $site->site_name ?? 'Site #' . $siteId;
    }

    /**
     * Search sites by name.
     *
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Array of Site entities
     */
    public function searchSites(string $query, int $limit = 20): array
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');

        if (trim($query) === '') {
            return [];
        }

        return $sites->find()
            ->contain(['Places'])
            ->leftJoinWith('Places')
            ->where([
                'OR' => [
                    ['Sites.site_name LIKE' => "%{$query}%"],
                    ['Places.place_name LIKE' => "%{$query}%"],
                    ['Places.place_state LIKE' => "%{$query}%"],
                ],
            ])
            ->orderBy(['Sites.site_name' => 'ASC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Get all sites ordered alphabetically.
     *
     * @param int $limit Result limit
     * @return array Array of Site entities
     */
    public function getAllSites(int $limit = 500): array
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');

        return $sites->find()
            ->contain(['Places'])
            ->orderBy(['Sites.site_name' => 'ASC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Create a new site.
     *
     * @param array<string, mixed> $data Site data
     * @return \App\Model\Entity\Site|false
     */
    public function createSite(array $data): \App\Model\Entity\Site|false
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');
        $site = $sites->newEntity($data);

        return $sites->save($site);
    }

    /**
     * Update an existing site.
     *
     * @param int $siteId Site ID
     * @param array<string, mixed> $data Site data
     * @return \App\Model\Entity\Site|false
     */
    public function updateSite(int $siteId, array $data): \App\Model\Entity\Site|false
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');
        $site = $sites->get($siteId);
        $sites->patchEntity($site, $data);

        return $sites->save($site);
    }

    /**
     * Delete a site.
     *
     * @param int $siteId Site ID
     * @return bool
     */
    public function deleteSite(int $siteId): bool
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');
        $site = $sites->get($siteId);

        return (bool)$sites->delete($site);
    }

    /**
     * Get sites formatted for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getSitesForSelect(): array
    {
        $list = $this->getSitesList();
        $results = [];
        foreach ($list as $id => $label) {
            $results[] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        return $results;
    }

    /**
     * Get sites as an associative list suitable for FormHelper selects.
     *
     * @param int|null $placeId Optional place filter
     * @param int $limit
     * @return array<int,string>
     */
    public function getSitesList(?int $placeId = null, int $limit = 500): array
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');

        $query = $sites->find()
            ->contain(['Places'])
            ->orderBy(['Sites.site_name' => 'ASC'])
            ->limit($limit);

        if ($placeId) {
            $query->where(['Sites.place_id' => $placeId]);
        }

        $list = [];
        foreach ($query->all() as $site) {
            $list[(int)$site->id] = $this->getDisplayLabel((int)$site->id);
        }

        return $list;
    }

    /**
     * Get sites filtered by place for AJAX responses.
     *
     * @param int $placeId
     * @return array<int,array{id:int,name:string}>
     */
    public function getSitesByPlace(int $placeId): array
    {
        if ($placeId <= 0) {
            return [];
        }

        $sites = TableRegistry::getTableLocator()->get('Sites');
        $query = $sites->find()
            ->where(['Sites.place_id' => $placeId])
            ->orderBy(['Sites.site_name' => 'ASC'])
            ->all();

        $results = [];
        foreach ($query as $site) {
            $results[] = [
                'id' => (int)$site->id,
                'name' => (string)($site->site_name ?? ''),
            ];
        }

        return $results;
    }
}
