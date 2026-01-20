<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * TeamSeasonService
 *
 * Service layer for TeamSeason entity operations and display data generation.
 */
class TeamSeasonService
{
    /**
     * Get a team season by ID with related data.
     *
     * @param int $teamSeasonId
     * @return \App\Model\Entity\TeamSeason|null
     */
    public function getTeamSeasonById(int $teamSeasonId): ?\App\Model\Entity\TeamSeason
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');

        return $teamSeasons->find()
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->first();
    }

    /**
     * Get a friendly display label for a team season.
     * Format: "TeamName StartYear-EndYear" or "TeamName Year"
     *
     * @param int $teamSeasonId
     * @return string
     */
    public function getDisplayLabel(int $teamSeasonId): string
    {
        $ts = $this->getTeamSeasonById($teamSeasonId);
        if (!$ts) {
            return 'Team Season #' . $teamSeasonId;
        }

        $teamName = $ts->team->team_name ?? 'Team';
        $season = $ts->season ?? null;
        $seasonLabel = '';

        if ($season) {
            $start = $season->start ?? null;
            $end = $season->end ?? null;
            if ($start && $end && $start != $end) {
                $seasonLabel = " {$start}-{$end}";
            } elseif ($start) {
                $seasonLabel = " {$start}";
            }
        }

        return trim($teamName . $seasonLabel);
    }

    /**
     * Get a sport-prefixed friendly label for a team season.
     * Format: "Men's Basketball 2023-2024" or "Women's Soccer 2024"
     *
     * @param int $teamSeasonId
     * @return string
     */
    public function getSportDisplayLabel(int $teamSeasonId): string
    {
        $ts = $this->getTeamSeasonById($teamSeasonId);
        if (!$ts) {
            return 'Team Season #' . $teamSeasonId;
        }

        $team = $ts->team ?? null;
        $sport = $team->sport ?? null;
        $gender = $team->gender ?? null;

        $prefix = '';
        if ($gender === 'M') {
            $prefix = "Men's ";
        } elseif ($gender === 'F') {
            $prefix = "Women's ";
        }

        $sportName = $sport->sport_name ?? null;
        $season = $ts->season ?? null;
        $seasonLabel = '';

        if ($season) {
            $start = $season->start ?? null;
            $end = $season->end ?? null;
            if ($start && $end && $start != $end) {
                $seasonLabel = " {$start}-{$end}";
            } elseif ($start) {
                $seasonLabel = " {$start}";
            }
        }

        if ($sportName) {
            return trim($prefix . $sportName . $seasonLabel);
        }

        // Fallback to team name if sport not available
        $teamName = $team->team_name ?? 'Team Season';

        return trim($teamName . $seasonLabel);
    }

    /**
     * Get all team seasons ordered by season and team.
     *
     * @return array
     */
    public function getAllTeamSeasons(): array
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');

        return $teamSeasons->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->orderBy(['Seasons.start' => 'DESC', 'Teams.team_name' => 'ASC'])
            ->all()
            ->toArray();
    }

    /**
     * Create a new team season.
     *
     * @param array $data TeamSeason data (team_id, season_id, etc.)
     * @return \App\Model\Entity\TeamSeason|false
     */
    public function createTeamSeason(array $data): \App\Model\Entity\TeamSeason|false
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');
        $teamSeason = $teamSeasons->newEntity($data);

        return $teamSeasons->save($teamSeason) ? $teamSeason : false;
    }

    /**
     * Update an existing team season.
     *
     * @param int $teamSeasonId
     * @param array $data Updated team season data
     * @return \App\Model\Entity\TeamSeason|false
     */
    public function updateTeamSeason(int $teamSeasonId, array $data): \App\Model\Entity\TeamSeason|false
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');
        $teamSeason = $teamSeasons->get($teamSeasonId);

        $teamSeason = $teamSeasons->patchEntity($teamSeason, $data);

        return $teamSeasons->save($teamSeason) ? $teamSeason : false;
    }

    /**
     * Delete a team season.
     *
     * @param int $teamSeasonId
     * @return bool
     */
    public function deleteTeamSeason(int $teamSeasonId): bool
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');
        $teamSeason = $teamSeasons->get($teamSeasonId);

        return $teamSeasons->delete($teamSeason);
    }

    /**
     * Get team seasons for select dropdown.
     *
     * @return array Array of [{id, label}, ...]
     */
    public function getTeamSeasonsForSelect(): array
    {
        $teamSeasons = $this->getAllTeamSeasons();
        $results = [];

        foreach ($teamSeasons as $ts) {
            $results[] = [
                'id' => $ts->id,
                'label' => $this->getSportDisplayLabel($ts->id),
            ];
        }

        return $results;
    }

    /**
     * Get team seasons as an associative list suitable for FormHelper selects.
     *
     * @param int $limit
     * @return array<int,string> Map of id => label
     */
    public function getTeamSeasonsList(int $limit = 200): array
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');

        $rows = $teamSeasons->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->orderBy(['Seasons.start' => 'DESC', 'Teams.team_name' => 'ASC'])
            ->limit($limit)
            ->all();

        $list = [];
        foreach ($rows as $ts) {
            $list[(int)$ts->id] = $this->getSportDisplayLabel((int)$ts->id);
        }

        return $list;
    }

    /**
     * Get team seasons as an associative list for roster forms.
     *
     * Format: "Team Name (Start-End)".
     *
     * @param int $limit
     * @return array<int,string>
     */
    public function getTeamSeasonsListForRosterSelect(int $limit = 200): array
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');

        $rows = $teamSeasons->find()
            ->contain(['Teams', 'Seasons'])
            ->select(['id', 'Teams.team_name', 'Seasons.start', 'Seasons.end'])
            ->orderByDesc('Seasons.start')
            ->limit($limit)
            ->all();

        $list = [];
        foreach ($rows as $ts) {
            /** @var \App\Model\Entity\TeamSeason $ts */
            $teamName = (string)($ts->team->team_name ?? 'Team');
            $start = $ts->season->start ?? null;
            $end = $ts->season->end ?? null;
            $seasonRange = trim((string)$start . '-' . (string)$end, '-');
            $list[(int)$ts->id] = $seasonRange !== ''
                ? $teamName . ' (' . $seasonRange . ')'
                : $teamName;
        }

        return $list;
    }

    /**
     * Get team seasons for a specific sport.
     *
     * @param string $sportName Sport name (e.g., "Men's Basketball")
     * @return array<int,\App\Model\Entity\TeamSeason>
     */
    public function getTeamSeasonsForSport(string $sportName): array
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');

        return $teamSeasons->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->matching('Teams.Sports', function ($q) use ($sportName) {
                return $q->where(['Sports.sport_name' => $sportName]);
            })
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();
    }
}
