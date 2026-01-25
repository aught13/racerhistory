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
    private SiteService $siteService;

    /**
     * Constructor.
     *
     * @param \App\Service\SiteService|null $siteService Site service instance
     */
    public function __construct(?SiteService $siteService = null)
    {
        $this->siteService = $siteService ?? new SiteService();
    }

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
     * Get places as an associative list suitable for FormHelper selects.
     *
     * Format: "Place Name, ST" (state omitted when empty).
     *
     * @param int $limit
     * @return array<int,string>
     */
    public function getPlacesList(int $limit = 500): array
    {
        $places = TableRegistry::getTableLocator()->get('Places');

        $rows = $places->find()
            ->orderBy(['Places.place_name' => 'ASC'])
            ->limit($limit)
            ->all();

        $list = [];
        foreach ($rows as $place) {
            /** @var \App\Model\Entity\Place $place */
            $label = (string)($place->place_name ?? '');
            $state = (string)($place->place_state ?? '');
            if ($state !== '') {
                $label .= ', ' . $state;
            }

            $list[(int)$place->id] = $label;
        }

        return $list;
    }

    /**
     * Get a site by ID.
     *
     * @param int $siteId Site ID
     * @return \App\Model\Entity\Site|null
     */
    public function getSiteById(int $siteId): ?\App\Model\Entity\Site
    {
        return $this->siteService->getSiteById($siteId);
    }

    /**
     * Get a friendly display label for a site (with place info).
     *
     * @param int $siteId Site ID
     * @return string
     */
    public function getSiteDisplayLabel(int $siteId): string
    {
        return $this->siteService->getDisplayLabel($siteId);
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
        return $this->siteService->searchSites($query, $limit);
    }

    /**
     * Get all sites ordered alphabetically with place info.
     *
     * @param int $limit Result limit
     * @return array Array of Site entities with Places
     */
    public function getAllSites(int $limit = 500): array
    {
        return $this->siteService->getAllSites($limit);
    }

    /**
     * Create a new site.
     *
     * @param array<string, mixed> $data Site data
     * @return \App\Model\Entity\Site|false
     */
    public function createSite(array $data): \App\Model\Entity\Site|false
    {
        return $this->siteService->createSite($data);
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
        return $this->siteService->updateSite($siteId, $data);
    }

    /**
     * Delete a site.
     *
     * @param int $siteId Site ID
     * @return bool
     */
    public function deleteSite(int $siteId): bool
    {
        return $this->siteService->deleteSite($siteId);
    }

    /**
     * Get sites formatted for select dropdown with place info.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getSitesForSelect(): array
    {
        return $this->siteService->getSitesForSelect();
    }
}
