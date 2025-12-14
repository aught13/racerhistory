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

        return $sites->find()->where(['Sites.id' => $siteId])->first();
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
            ->where(['Sites.site_name LIKE' => "%{$query}%"])
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
        $sites = $this->getAllSites();
        $results = [];

        foreach ($sites as $site) {
            $results[] = [
                'id' => $site->id,
                'label' => $site->site_name,
            ];
        }

        return $results;
    }
}
