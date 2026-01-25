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
     * Get team seasons as an associative list with sport and season range.
     *
     * Format: "Team Name (Sport) — Start-End".
     *
     * @param int $limit
     * @return array<int,string>
     */
    public function getTeamSeasonsDetailedList(int $limit = 500): array
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');

        $rows = $teamSeasons->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->orderByDesc('Seasons.start')
            ->limit($limit)
            ->all();

        $list = [];
        foreach ($rows as $ts) {
            /** @var \App\Model\Entity\TeamSeason $ts */
            $sportName = $ts->team->sport->sport_name ?? 'Unknown';
            $list[(int)$ts->id] = sprintf(
                '%s (%s) — %s-%s',
                ($ts->team->team_name ?? 'Team'),
                $sportName,
                ($ts->season->start ?? ''),
                ($ts->season->end ?? '')
            );
        }

        return $list;
    }

    /**
     * Get overall and conference record summary for a team season.
     *
     * @param int $teamSeasonId
     * @return array<string,int|float|null>
     */
    public function getRecordSummary(int $teamSeasonId): array
    {
        $gamesTable = TableRegistry::getTableLocator()->get('Games');
        $query = $gamesTable->find();

        $tieCondition = '((Games.pts_mur IS NOT NULL AND Games.pts_opp IS NOT NULL AND Games.pts_mur = Games.pts_opp)' .
            " OR (Games.w = '1' AND Games.l = '1'))";

        $row = $query
            ->select([
                'overall_wins' => $query->newExpr(
                    "SUM(CASE WHEN {$tieCondition} THEN 0 WHEN Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'overall_losses' => $query->newExpr(
                    "SUM(CASE WHEN {$tieCondition} THEN 0 WHEN Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
                'overall_ties' => $query->newExpr(
                    "SUM(CASE WHEN {$tieCondition} THEN 1 ELSE 0 END)",
                ),
                'conf_wins' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND {$tieCondition} THEN 0 " .
                        "WHEN GameTypes.conf = 1 AND Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'conf_losses' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND {$tieCondition} THEN 0 " .
                        "WHEN GameTypes.conf = 1 AND Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
                'conf_ties' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND {$tieCondition} THEN 1 ELSE 0 END)",
                ),
            ])
            ->where(['Games.team_season_id' => $teamSeasonId])
            ->leftJoinWith('GameTypes')
            ->enableHydration(false)
            ->first();

        $ow = isset($row['overall_wins']) ? (int)$row['overall_wins'] : 0;
        $ol = isset($row['overall_losses']) ? (int)$row['overall_losses'] : 0;
        $ot = isset($row['overall_ties']) ? (int)$row['overall_ties'] : 0;
        $cw = isset($row['conf_wins']) ? (int)$row['conf_wins'] : 0;
        $cl = isset($row['conf_losses']) ? (int)$row['conf_losses'] : 0;
        $ct = isset($row['conf_ties']) ? (int)$row['conf_ties'] : 0;

        $overallTotal = $ow + $ol + $ot;
        $confTotal = $cw + $cl + $ct;

        return [
            'overall_wins' => $ow,
            'overall_losses' => $ol,
            'overall_ties' => $ot,
            'overall_pct' => $overallTotal > 0 ? round(($ow + (0.5 * $ot)) / $overallTotal, 3) : null,
            'conf_wins' => $cw,
            'conf_losses' => $cl,
            'conf_ties' => $ct,
            'conf_pct' => $confTotal > 0 ? round(($cw + (0.5 * $ct)) / $confTotal, 3) : null,
        ];
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
