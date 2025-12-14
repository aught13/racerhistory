<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * PlaceService
 *
 * Service layer for Place entity CRUD and business logic.
 * Places are geographic locations; Sites are specific venues within places.
 */
class PlaceService
{
    /**
     * Get a place by ID.
     *
     * @param int $placeId Place ID
     * @return \App\Model\Entity\Place|null
     */
    public function getPlaceById(int $placeId): ?\App\Model\Entity\Place
    {
        $places = TableRegistry::getTableLocator()->get('Places');

        return $places->find()->where(['Places.id' => $placeId])->first();
    }

    /**
     * Get a friendly display label for a place.
     *
     * @param int $placeId Place ID
     * @return string
     */
    public function getDisplayLabel(int $placeId): string
    {
        $place = $this->getPlaceById($placeId);
        if (!$place) {
            return 'Place #' . $placeId;
        }

        return $place->place_name ?? 'Place #' . $placeId;
    }

    /**
     * Search places by name or state.
     *
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Array of Place entities
     */
    public function searchPlaces(string $query, int $limit = 20): array
    {
        $places = TableRegistry::getTableLocator()->get('Places');

        if (trim($query) === '') {
            return [];
        }

        return $places->find()
            ->where([
                'OR' => [
                    ['Places.place_name LIKE' => "%{$query}%"],
                    ['Places.place_state LIKE' => "%{$query}%"],
                ],
            ])
            ->orderBy(['Places.place_name' => 'ASC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Get all places ordered alphabetically.
     *
     * @param int $limit Result limit
     * @return array Array of Place entities
     */
    public function getAllPlaces(int $limit = 500): array
    {
        $places = TableRegistry::getTableLocator()->get('Places');

        return $places->find()
            ->orderBy(['Places.place_name' => 'ASC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Create a new place.
     *
     * @param array<string, mixed> $data Place data
     * @return \App\Model\Entity\Place|false
     */
    public function createPlace(array $data): \App\Model\Entity\Place|false
    {
        $places = TableRegistry::getTableLocator()->get('Places');
        $place = $places->newEntity($data);

        return $places->save($place);
    }

    /**
     * Update an existing place.
     *
     * @param int $placeId Place ID
     * @param array<string, mixed> $data Place data
     * @return \App\Model\Entity\Place|false
     */
    public function updatePlace(int $placeId, array $data): \App\Model\Entity\Place|false
    {
        $places = TableRegistry::getTableLocator()->get('Places');
        $place = $places->get($placeId);
        $places->patchEntity($place, $data);

        return $places->save($place);
    }

    /**
     * Delete a place.
     *
     * @param int $placeId Place ID
     * @return bool
     */
    public function deletePlace(int $placeId): bool
    {
        $places = TableRegistry::getTableLocator()->get('Places');
        $place = $places->get($placeId);

        return (bool)$places->delete($place);
    }

    /**
     * Get places formatted for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getPlacesForSelect(): array
    {
        $places = $this->getAllPlaces();
        $results = [];

        foreach ($places as $place) {
            $results[] = [
                'id' => $place->id,
                'label' => $place->place_name,
            ];
        }

        return $results;
    }

    /**
     * Get a site by ID.
     *
     * @param int $siteId Site ID
     * @return \App\Model\Entity\Site|null
     */
    public function getSiteById(int $siteId): ?\App\Model\Entity\Site
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');

        return $sites->find()->contain(['Places'])->where(['Sites.id' => $siteId])->first();
    }

    /**
     * Get a friendly display label for a site (with place info).
     *
     * @param int $siteId Site ID
     * @return string
     */
    public function getSiteDisplayLabel(int $siteId): string
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
     * Search sites by name, place name, or place state.
     *
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Array of Site entities with Places
     */
    public function searchSites(string $query, int $limit = 20): array
    {
        $sites = TableRegistry::getTableLocator()->get('Sites');

        if (trim($query) === '') {
            return [];
        }

        return $sites->find()
            ->contain(['Places'])
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
     * Get all sites ordered alphabetically with place info.
     *
     * @param int $limit Result limit
     * @return array Array of Site entities with Places
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
     * Get sites formatted for select dropdown with place info.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getSitesForSelect(): array
    {
        $sites = $this->getAllSites();
        $results = [];

        foreach ($sites as $site) {
            $label = $this->getSiteDisplayLabel($site->id);
            $results[] = [
                'id' => $site->id,
                'label' => $label,
            ];
        }

        return $results;
    }
}
