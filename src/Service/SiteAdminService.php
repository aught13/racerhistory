<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;

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
     * @return bool
     */
    public function deleteSite(int|string $id): bool
    {
        $site = $this->getSitesTable()->get($id);

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
    private function getSitesTable(): \App\Model\Table\SitesTable
    {
        /** @var \App\Model\Table\SitesTable $table */
        $table = TableRegistry::getTableLocator()->get('Sites');

        return $table;
    }

    /**
     * @return \App\Model\Table\PlacesTable
     */
    private function getPlacesTable(): \App\Model\Table\PlacesTable
    {
        /** @var \App\Model\Table\PlacesTable $table */
        $table = TableRegistry::getTableLocator()->get('Places');

        return $table;
    }
}
