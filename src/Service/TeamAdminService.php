<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;

/**
 * TeamAdminService
 *
 * Owns the complete administrative team management slice used by
 * Admin/TeamsController. This keeps request handling in the controller while
 * centralizing all CRUD orchestration, list preparation, and popup-form save
 * flows in a service layer that can be tested independently.
 *
 * Notes:
 * - Keep this service free of HTTP concerns (Flash, Redirect, Request checks).
 * - Preserve returned array keys because controllers and tests rely on them.
 * - If team form behavior changes, update both standard and popup save paths.
 */
class TeamAdminService
{
    /**
     * Return index page data.
     *
     * @return array{teams:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $teams = $this->getTeamsTable()->find()
            ->contain(['Sports'])
            ->all();

        return compact('teams');
    }

    /**
     * Return view page data.
     *
     * @param string|int $id Team identifier
     * @return array{team:\App\Model\Entity\Team}
     */
    public function getViewData(int|string $id): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->get($id, contain: ['Sports']);

        return compact('team');
    }

    /**
     * Return add form data including optional sport pre-selection.
     *
     * @param int|null $sportId Sport id from query string
     * @return array{team:\App\Model\Entity\Team,sports:\Cake\Datasource\ResultSetInterface}
     */
    public function getAddFormData(?int $sportId = null): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->newEmptyEntity();
        if ($sportId) {
            $team->sport_id = $sportId;
        }

        $sports = $this->getSportsTable()->find('list', limit: 200)->all();

        return compact('team', 'sports');
    }

    /**
     * Create a new team from form data.
     *
     * @param array<string,mixed> $data Request payload
     * @param int|null $sportId Optional pre-selected sport id
     * @return array{success:bool,team:\App\Model\Entity\Team}
     */
    public function saveNewTeam(array $data, ?int $sportId = null): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->newEmptyEntity();
        if ($sportId && empty($data['sport_id'])) {
            $data['sport_id'] = $sportId;
        }

        $team = $this->getTeamsTable()->patchEntity($team, $data);
        $success = (bool)$this->getTeamsTable()->save($team);

        return compact('success', 'team');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Team identifier
     * @return array{team:\App\Model\Entity\Team,sports:\Cake\Datasource\ResultSetInterface}
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->get($id, contain: ['Sports']);
        $sports = $this->getSportsTable()->find('list', limit: 200)->all();

        return compact('team', 'sports');
    }

    /**
     * Update an existing team.
     *
     * @param string|int $id Team identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,team:\App\Model\Entity\Team}
     */
    public function saveExistingTeam(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->get($id, contain: ['Sports']);
        $team = $this->getTeamsTable()->patchEntity($team, $data);
        $success = (bool)$this->getTeamsTable()->save($team);

        return compact('success', 'team');
    }

    /**
     * Delete a team.
     *
     * @param string|int $id Team identifier
     * @return bool
     */
    public function deleteTeam(int|string $id): bool
    {
        $team = $this->getTeamsTable()->get($id);

        return (bool)$this->getTeamsTable()->delete($team);
    }

    /**
     * Bulk delete teams by identifier list.
     *
     * @param array<mixed> $rawIds Raw identifier list from request
     * @return int Number of deleted records
     */
    public function bulkDeleteTeams(array $rawIds): int
    {
        $teamIds = $this->sanitizeIdentifierList($rawIds);
        if ($teamIds === []) {
            return 0;
        }

        $deletedCount = 0;
        foreach ($teamIds as $id) {
            try {
                $team = $this->getTeamsTable()->get($id);
                if ($this->getTeamsTable()->delete($team)) {
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
     * Save a team from popup add form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @return array<string,mixed>
     */
    public function createTeamFromPopup(array $data): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->newEmptyEntity();
        $team = $this->getTeamsTable()->patchEntity($team, $data);

        if ($this->getTeamsTable()->save($team)) {
            return [
                'success' => true,
                'message' => 'Team has been added successfully.',
                'newOption' => [
                    'value' => $team->id,
                    'text' => $team->team_name,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($team) ?: ['Unable to save team. Please try again.'],
        ];
    }

    /**
     * Convert entity validation errors into frontend-friendly strings.
     *
     * @param \Cake\Datasource\EntityInterface $entity Team entity
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
     * @return \App\Model\Table\TeamsTable
     */
    private function getTeamsTable(): \App\Model\Table\TeamsTable
    {
        /** @var \App\Model\Table\TeamsTable $table */
        $table = TableRegistry::getTableLocator()->get('Teams');

        return $table;
    }

    /**
     * @return \App\Model\Table\SportsTable
     */
    private function getSportsTable(): \App\Model\Table\SportsTable
    {
        /** @var \App\Model\Table\SportsTable $table */
        $table = TableRegistry::getTableLocator()->get('Sports');

        return $table;
    }
}
