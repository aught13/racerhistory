<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\SeasonsTable;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;

/**
 * SeasonAdminService
 *
 * Encapsulates the complete admin orchestration for season management so the
 * controller only handles request validation, Flash messaging, and redirects.
 * This service owns list/detail queries, create/update/delete, and popup-form
 * save behavior.
 *
 * Notes:
 * - Keep season navigation logic (previous/next season) here.
 * - Preserve compact return contracts because controller tests assert behavior.
 * - Avoid introducing HTTP-layer dependencies inside service methods.
 */
class SeasonAdminService
{
    private RbacPermissionService $rbacPermissionService;

    /**
     * Return index page data.
     *
     * @return array{seasons:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $seasons = $this->getSeasonsTable()->find()
            ->contain(['TeamSeasons'])
            ->all();

        return compact('seasons');
    }

    /**
     * Return detail page data, including previous/next season navigation.
     *
     * @param string|int $id Season identifier
     * @return array{season:\App\Model\Entity\Season,previousSeason:\App\Model\Entity\Season|null,nextSeason:\App\Model\Entity\Season|null}
     */
    public function getViewData(int|string $id): array
    {
        /** @var \App\Model\Entity\Season $season */
        $season = $this->getSeasonsTable()->get($id, contain: ['TeamSeasons' => ['Teams']]);

        /** @var \App\Model\Entity\Season|null $previousSeason */
        $previousSeason = $this->getSeasonsTable()->find()
            ->where(['end <' => $season->end])
            ->orderByDesc('end')
            ->first();

        /** @var \App\Model\Entity\Season|null $nextSeason */
        $nextSeason = $this->getSeasonsTable()->find()
            ->where(['end >' => $season->end])
            ->orderByAsc('end')
            ->first();

        return compact('season', 'previousSeason', 'nextSeason');
    }

    /**
     * Return add form data.
     *
     * @return array{season:\App\Model\Entity\Season}
     */
    public function getAddFormData(): array
    {
        /** @var \App\Model\Entity\Season $season */
        $season = $this->getSeasonsTable()->newEmptyEntity();

        return compact('season');
    }

    /**
     * Save new season.
     *
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,season:\App\Model\Entity\Season}
     */
    public function saveNewSeason(array $data): array
    {
        /** @var \App\Model\Entity\Season $season */
        $season = $this->getSeasonsTable()->newEmptyEntity();
        $season = $this->getSeasonsTable()->patchEntity($season, $data);
        $success = (bool)$this->getSeasonsTable()->save($season);

        return compact('success', 'season');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Season identifier
     * @return array{season:\App\Model\Entity\Season}
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\Season $season */
        $season = $this->getSeasonsTable()->get($id, contain: ['TeamSeasons']);

        return compact('season');
    }

    /**
     * Save existing season.
     *
     * @param string|int $id Season identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,season:\App\Model\Entity\Season}
     */
    public function saveExistingSeason(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\Season $season */
        $season = $this->getSeasonsTable()->get($id, contain: ['TeamSeasons']);
        $season = $this->getSeasonsTable()->patchEntity($season, $data);
        $success = (bool)$this->getSeasonsTable()->save($season);

        return compact('success', 'season');
    }

    /**
     * Delete a season.
     *
     * @param string|int $id Season identifier
     * @param mixed $identity Current authenticated identity
     * @return bool
     */
    public function deleteSeason(int|string $id, mixed $identity = null): bool
    {
        // If RBAC is available and identity provided via global request, use RBAC service to guard.
        if (!isset($this->rbacPermissionService)) {
            $this->rbacPermissionService = new RbacPermissionService();
        }

        // Admins may delete freely
        if ($this->rbacPermissionService->isAdmin($identity)) {
            $season = $this->getSeasonsTable()->get($id);

            return (bool)$this->getSeasonsTable()->delete($season);
        }

        // For non-admins, require an identity and explicit RBAC allow
        if ($identity === null) {
            return false;
        }

        if (!$this->rbacPermissionService->can($identity, 'Seasons', 'delete')) {
            return false;
        }

        $season = $this->getSeasonsTable()->get($id);

        return (bool)$this->getSeasonsTable()->delete($season);
    }

    /**
     * Bulk delete seasons by identifier list.
     *
     * @param array<mixed> $rawIds Raw identifier list from request
     * @param mixed $identity Current authenticated identity
     * @return int Number of deleted records
     */
    public function bulkDeleteSeasons(array $rawIds, mixed $identity = null): int
    {
        $seasonIds = $this->sanitizeIdentifierList($rawIds);
        if ($seasonIds === []) {
            return 0;
        }

        if (!isset($this->rbacPermissionService)) {
            $this->rbacPermissionService = new RbacPermissionService();
        }

        // Admins can delete freely
        if ($this->rbacPermissionService->isAdmin($identity)) {
            $deletedCount = 0;
            foreach ($seasonIds as $id) {
                try {
                    $season = $this->getSeasonsTable()->get($id);
                    if ($this->getSeasonsTable()->delete($season)) {
                        $deletedCount++;
                    }
                } catch (RecordNotFoundException $exception) {
                    continue;
                }
            }

            return $deletedCount;
        }

        // If no identity present for non-admin, deny all deletes
        if ($identity === null) {
            return 0;
        }

        // Apply RBAC scope to the query to determine which seasons are deletable
        $query = $this->getSeasonsTable()->find()
            ->select(['id'])
            ->where(['Seasons.id IN' => $seasonIds])
            ->disableHydration();

        $scoped = $this->rbacPermissionService->scopeQuery($identity, 'Seasons', $query, 'delete', 'user_id');

        $allowed = [];
        foreach ($scoped->all() as $row) {
            if (is_array($row) && !empty($row['id'])) {
                $allowed[] = (int)$row['id'];
            }
        }

        if ($allowed === []) {
            return 0;
        }

        $deletedCount = 0;
        foreach ($allowed as $id) {
            try {
                $season = $this->getSeasonsTable()->get($id);
                if ($this->getSeasonsTable()->delete($season)) {
                    $deletedCount++;
                }
            } catch (RecordNotFoundException $exception) {
                continue;
            }
        }

        return $deletedCount;
    }

    /**
     * Validate and normalize bulk identifier input.
     *
     * @param array<mixed> $rawIds Raw identifier list
     * @return array<int>
     */
    public function sanitizeIdentifierList(array $rawIds): array
    {
        $filtered = array_values(array_filter($rawIds, static function ($value) {
            return $value !== '' && $value !== null && ctype_digit((string)$value);
        }));

        return array_map('intval', $filtered);
    }

    /**
     * Save a season from popup add form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @return array<string,mixed>
     */
    public function createSeasonFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\Season $season */
        $season = $this->getSeasonsTable()->newEmptyEntity();
        $season = $this->getSeasonsTable()->patchEntity($season, $data);

        if ($this->getSeasonsTable()->save($season)) {
            return [
                'success' => true,
                'message' => 'Season has been added successfully.',
                'newOption' => [
                    'value' => $season->id,
                    'text' => $season->start . '-' . $season->end,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($season) ?: ['Unable to save season. Please try again.'],
        ];
    }

    /**
     * Convert entity validation errors into frontend-friendly strings.
     *
     * @param \Cake\Datasource\EntityInterface $entity Season entity
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
     * @return \App\Model\Table\SeasonsTable
     */
    private function getSeasonsTable(): SeasonsTable
    {
        /** @var \App\Model\Table\SeasonsTable $table */
        $table = TableRegistry::getTableLocator()->get('Seasons');

        return $table;
    }
}
