<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Team;
use Cake\ORM\TableRegistry;

/**
 * TeamService
 *
 * Service layer for Team entity CRUD and business logic.
 */
class TeamService
{
    private SportService $sportService;

    /**
     * Constructor.
     *
     * @param \App\Service\SportService|null $sportService Sport service instance
     */
    public function __construct(?SportService $sportService = null)
    {
        $this->sportService = $sportService ?? new SportService();
    }

    /**
     * Get sport service instance.
     *
     * @return \App\Service\SportService
     */
    public function getSportService(): SportService
    {
        return $this->sportService;
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
            ->contain(['Sports'])
            ->first();

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
        $teams = TableRegistry::getTableLocator()->get('Teams');
        $query = $teams->find()
            ->contain(['Sports'])
            ->orderBy(['Teams.team_name' => 'ASC']);

        if ($sportId) {
            $query->where(['Teams.sport_id' => $sportId]);
        }

        return $query->all()->toArray();
    }

    /**
     * Create a new team.
     *
     * @param array<string, mixed> $data Team data
     * @return \App\Model\Entity\Team|false
     */
    public function createTeam(array $data): Team|false
    {
        $teams = TableRegistry::getTableLocator()->get('Teams');
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
        $teams = TableRegistry::getTableLocator()->get('Teams');
        $team = $teams->get($teamId);
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
            $results[] = [
                'id' => $team->id,
                'label' => $this->getDisplayLabel($team->id, true),
                'sport' => $team->sport->sport_name ?? null,
            ];
        }

        return $results;
    }
}
