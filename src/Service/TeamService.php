<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Team;
use App\Model\Table\TeamsTable;
use Cake\ORM\TableRegistry;

/**
 * TeamService
 *
 * Service layer for Team entity CRUD and business logic.
 */
class TeamService
{
    private TeamSportContextService $teamSportContextService;

    /**
     * Constructor.
     *
     * @param \App\Service\TeamSportContextService|null $teamSportContextService Team sport context service
     */
    public function __construct(?TeamSportContextService $teamSportContextService = null)
    {
        $this->teamSportContextService = $teamSportContextService ?? new TeamSportContextService();
    }

    /**
     * Get team sport context service instance.
     *
     * @return \App\Service\TeamSportContextService
     */
    public function getTeamSportContextService(): TeamSportContextService
    {
        return $this->teamSportContextService;
    }

    /**
     * Get a team by ID with sport association.
     *
     * @param int $teamId Team ID
     * @return \App\Model\Entity\Team|null
     */
    public function getTeamById(int $teamId): ?Team
    {
        $teams = TableRegistry::getTableLocator()->get('Teams');

        $team = $teams->find()
            ->where(['Teams.id' => $teamId])
            ->first();

        if ($team instanceof Team) {
            $this->teamSportContextService->attachSportContextToTeam($team);
        }

        return $team instanceof Team ? $team : null;
    }

    /**
     * Get a friendly display label for a team.
     * Format: "TeamName" or "Men's TeamName" / "Women's TeamName"
     *
     * @param int $teamId Team ID
     * @param bool $includeGender Include gender prefix
     * @return string
     */
    public function getDisplayLabel(int $teamId, bool $includeGender = false): string
    {
        $team = $this->getTeamById($teamId);
        if (!$team) {
            return 'Team #' . $teamId;
        }

        $name = $team->team_name ?? 'Team';

        if ($includeGender && !empty($team->gender)) {
            $prefix = $team->gender === 'M' ? "Men's " : ($team->gender === 'F' ? "Women's " : '');
            $name = $prefix . $name;
        }

        return $name;
    }

    /**
     * Get all teams with optional sport filter.
     *
     * @param int|null $sportId Optional sport ID filter
     * @return array Array of Team entities
     */
    public function getAllTeams(?int $sportId = null): array
    {
        /** @var \App\Model\Table\TeamsTable $teams */
        $teams = TableRegistry::getTableLocator()->get('Teams');
        $query = $teams->find()
            ->orderBy(['Teams.team_name' => 'ASC']);

        if ($sportId) {
            $sportKey = $this->teamSportContextService->resolveSportKeyFromId($sportId);
            if ($sportKey !== null) {
                $query->where($this->teamSportContextService->buildSportFilterConditions($sportKey));
            } elseif ($teams->getSchema()->hasColumn('sport_id')) {
                $query->where(['Teams.sport_id' => $sportId]);
            } else {
                $query->where(['Teams.id' => -1]);
            }
        }

        $rows = $query->all()->toArray();
        foreach ($rows as $row) {
            if ($row instanceof Team) {
                $this->teamSportContextService->attachSportContextToTeam($row);
            }
        }

        return $rows;
    }

    /**
     * Create a new team.
     *
     * @param array<string, mixed> $data Team data
     * @return \App\Model\Entity\Team|false
     */
    public function createTeam(array $data): Team|false
    {
        /** @var \App\Model\Table\TeamsTable $teams */
        $teams = TableRegistry::getTableLocator()->get('Teams');
        $data = $this->normalizeSportPayload($teams, $data);
        $team = $teams->newEntity($data);

        return $teams->save($team);
    }

    /**
     * Update an existing team.
     *
     * @param int $teamId Team ID
     * @param array<string, mixed> $data Team data
     * @return \App\Model\Entity\Team|false
     */
    public function updateTeam(int $teamId, array $data): Team|false
    {
        /** @var \App\Model\Table\TeamsTable $teams */
        $teams = TableRegistry::getTableLocator()->get('Teams');
        $team = $teams->get($teamId);
        $data = $this->normalizeSportPayload($teams, $data);
        $teams->patchEntity($team, $data);

        return $teams->save($team);
    }

    /**
     * Delete a team.
     *
     * @param int $teamId Team ID
     * @return bool
     */
    public function deleteTeam(int $teamId): bool
    {
        $teams = TableRegistry::getTableLocator()->get('Teams');
        $team = $teams->get($teamId);

        return (bool)$teams->delete($team);
    }

    /**
     * Get teams formatted for select dropdown.
     *
     * @param int|null $sportId Optional sport ID filter
     * @return array Array of [{id, label, sport}, ...]
     */
    public function getTeamsForSelect(?int $sportId = null): array
    {
        $teams = $this->getAllTeams($sportId);
        $results = [];

        foreach ($teams as $team) {
            if (!($team instanceof Team)) {
                continue;
            }

            $sportName = $this->teamSportContextService->resolveSportNameFromTeam($team);
            $results[] = [
                'id' => $team->id,
                'label' => $this->getDisplayLabel($team->id, true),
                'sport' => $sportName,
            ];
        }

        return $results;
    }

    /**
     * @param \App\Model\Table\TeamsTable $teamsTable
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizeSportPayload(TeamsTable $teamsTable, array $data): array
    {
        $sportKey = strtolower(trim((string)($data['sport_key'] ?? '')));
        $sportId = isset($data['sport_id']) ? (int)$data['sport_id'] : 0;

        if ($sportKey !== '' && ctype_digit($sportKey)) {
            $resolved = $this->teamSportContextService->resolveSportKeyFromId((int)$sportKey);
            if ($resolved !== null) {
                $sportKey = $resolved;
                $data['sport_key'] = $sportKey;
            }
        }

        if ($sportKey === '' && $sportId > 0) {
            $resolved = $this->teamSportContextService->resolveSportKeyFromId($sportId);
            if ($resolved !== null) {
                $sportKey = $resolved;
                $data['sport_key'] = $sportKey;
            }
        }

        if ($sportKey !== '' && $teamsTable->getSchema()->hasColumn('sport_id') && $sportId <= 0) {
            $resolvedId = $this->teamSportContextService->resolveSportIdFromKey($sportKey);
            if ($resolvedId !== null) {
                $data['sport_id'] = $resolvedId;
            }
        }

        if (!$teamsTable->getSchema()->hasColumn('sport_id') && array_key_exists('sport_id', $data)) {
            unset($data['sport_id']);
        }

        return $data;
    }
}
