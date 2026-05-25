<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Season;
use App\Model\Entity\TeamSeason;
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
    public function getTeamSeasonById(int $teamSeasonId): ?TeamSeason
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');

        $teamSeason = $teamSeasons->find()
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->first();

        return $teamSeason instanceof TeamSeason ? $teamSeason : null;
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

        if ($season instanceof Season) {
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

        if ($season instanceof Season) {
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
    public function createTeamSeason(array $data): TeamSeason|false
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
    public function updateTeamSeason(int $teamSeasonId, array $data): TeamSeason|false
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
            if (!($ts instanceof TeamSeason)) {
                continue;
            }
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
                ($ts->season->end ?? ''),
            );
        }

        return $list;
    }

    /**
     * Get overall and conference record summary for a team season.
     *
     * @param int $teamSeasonId
     * @return array<string,mixed>
     */
    public function getRecordSummary(int $teamSeasonId): array
    {
        $gameService = new GameService();
        $games = $gameService->getGamesByTeamSeason($teamSeasonId, 'ASC');

        $initRecord = static fn(): array => [
            'W' => 0,
            'L' => 0,
            'T' => 0,
        ];

        $applyResult = static function (array &$record, string $result): void {
            if ($result === 'W') {
                $record['W']++;
            } elseif ($result === 'L') {
                $record['L']++;
            } elseif ($result === 'T') {
                $record['T']++;
            }
        };

        $formatRecord = static function (array $record): array {
            $total = $record['W'] + $record['L'] + $record['T'];
            $pct = $total === 0 ? null : round(($record['W'] + (0.5 * $record['T'])) / $total, 3);

            return [
                'W' => $record['W'],
                'L' => $record['L'],
                'T' => $record['T'],
                'Pct' => $pct,
            ];
        };

        $overall = $initRecord();
        $overallHome = $initRecord();
        $overallRoad = $initRecord();
        $overallNeutral = $initRecord();

        $conf = $initRecord();
        $confHome = $initRecord();
        $confRoad = $initRecord();
        $confNeutral = $initRecord();
        $confByType = [];
        $confTypeLabels = [];

        $confTourn = $initRecord();
        $confTournHome = $initRecord();
        $confTournRoad = $initRecord();
        $confTournNeutral = $initRecord();
        $confTournByType = [];
        $confTournTypeLabels = [];

        $postseason = $initRecord();
        $postseasonHome = $initRecord();
        $postseasonRoad = $initRecord();
        $postseasonNeutral = $initRecord();
        $postseasonByType = [];
        $postTypeLabels = [];

        $groupLabels = [
            'Overall' => 'Overall',
            'Conference' => 'Conference',
            'Conference Tournament' => 'Conference Tournament',
            'Postseason' => 'Postseason',
        ];

        $getTypeKey = static function ($gameType, string $group): ?string {
            if (!$gameType) {
                return null;
            }

            if ($group === 'Postseason') {
                if (!empty($gameType->abr)) {
                    $label = (string)$gameType->abr;
                } elseif (!empty($gameType->game_type_name)) {
                    $label = (string)$gameType->game_type_name;
                    if (strlen($label) > 8) {
                        $label = substr($label, 0, 8);
                    }
                } else {
                    return null;
                }

                $label = trim($label);

                return $label !== '' ? $label : null;
            }

            if ($group === 'Conference Tournament') {
                $label = $gameType->game_type_name ?? $gameType->abr ?? $gameType->id ?? 'unknown';
            } else {
                $label = $gameType->abr ?? $gameType->game_type_name ?? $gameType->id ?? 'unknown';
            }

            return (string)$label;
        };

        foreach ($games as $game) {
            $result = $gameService->getResultFlag($game);
            if ($result === null) {
                continue;
            }

            $applyResult($overall, $result);

            $hrn = (int)($game->hrn ?? 0);
            if ($hrn === 1) {
                $applyResult($overallHome, $result);
            } elseif ($hrn === 2) {
                $applyResult($overallRoad, $result);
            } elseif ($hrn === 3) {
                $applyResult($overallNeutral, $result);
            }

            $gameType = $game->game_type ?? null;
            $isConf = !empty($gameType) && !empty($gameType->conf);
            $isPost = !empty($gameType) && !empty($gameType->post);

            $confTypeKey = $getTypeKey($gameType, 'Conference');
            $confTournTypeKey = $getTypeKey($gameType, 'Conference Tournament');
            $postTypeKey = $getTypeKey($gameType, 'Postseason');

            if ($isConf && !$isPost) {
                $applyResult($conf, $result);
                if ($hrn === 1) {
                    $applyResult($confHome, $result);
                } elseif ($hrn === 2) {
                    $applyResult($confRoad, $result);
                } elseif ($hrn === 3) {
                    $applyResult($confNeutral, $result);
                }
                if ($confTypeKey !== null) {
                    $confByType[$confTypeKey] ??= $initRecord();
                    $applyResult($confByType[$confTypeKey], $result);
                    $confTypeLabels[$confTypeKey] = true;
                }
            }

            if ($isConf && $isPost) {
                $applyResult($confTourn, $result);
                if ($hrn === 1) {
                    $applyResult($confTournHome, $result);
                } elseif ($hrn === 2) {
                    $applyResult($confTournRoad, $result);
                } elseif ($hrn === 3) {
                    $applyResult($confTournNeutral, $result);
                }
                if ($confTournTypeKey !== null) {
                    $confTournByType[$confTournTypeKey] ??= $initRecord();
                    $applyResult($confTournByType[$confTournTypeKey], $result);
                    $confTournTypeLabels[$confTournTypeKey] = true;
                }
            }

            if (!$isConf && $isPost) {
                $applyResult($postseason, $result);
                if ($hrn === 1) {
                    $applyResult($postseasonHome, $result);
                } elseif ($hrn === 2) {
                    $applyResult($postseasonRoad, $result);
                } elseif ($hrn === 3) {
                    $applyResult($postseasonNeutral, $result);
                }
                if ($postTypeKey !== null) {
                    $postseasonByType[$postTypeKey] ??= $initRecord();
                    $applyResult($postseasonByType[$postTypeKey], $result);
                    $postTypeLabels[$postTypeKey] = true;
                }
            }
        }

        if (count($confTypeLabels) === 1) {
            $groupLabels['Conference'] = (string)array_key_first($confTypeLabels);
        }
        if (count($confTournTypeLabels) === 1) {
            $groupLabels['Conference Tournament'] = (string)array_key_first($confTournTypeLabels);
        }
        if (!empty($postTypeLabels)) {
            $groupLabels['Postseason'] = (string)array_key_last($postTypeLabels);
        } else {
            $groupLabels['Postseason'] = '-';
        }

        $overallSplits = [
            'Home' => $formatRecord($overallHome),
            'Road' => $formatRecord($overallRoad),
            'Neutral' => $formatRecord($overallNeutral),
        ];

        $confSplits = [
            'Home' => $formatRecord($confHome),
            'Road' => $formatRecord($confRoad),
            'Neutral' => $formatRecord($confNeutral),
        ];

        if (count($confByType) > 1) {
            $confSplits['By Type'] = array_merge(
                [$groupLabels['Conference'] => $formatRecord($conf)],
                array_map($formatRecord, $confByType),
            );
        }

        $confTournSplits = [
            'Home' => $formatRecord($confTournHome),
            'Road' => $formatRecord($confTournRoad),
            'Neutral' => $formatRecord($confTournNeutral),
        ];
        if (count($confTournByType) > 1) {
            $confTournSplits['By Type'] = array_merge(
                [$groupLabels['Conference Tournament'] => $formatRecord($confTourn)],
                array_map($formatRecord, $confTournByType),
            );
        }

        $postseasonSplits = [
            'Home' => $formatRecord($postseasonHome),
            'Road' => $formatRecord($postseasonRoad),
            'Neutral' => $formatRecord($postseasonNeutral),
        ];
        if (count($postseasonByType) > 1) {
            $postseasonSplits['By Type'] = array_merge(
                [$groupLabels['Postseason'] => $formatRecord($postseason)],
                array_map($formatRecord, $postseasonByType),
            );
        }

        return [
            'Overall' => [
                'label' => $groupLabels['Overall'],
                'totals' => $formatRecord($overall),
                'splits' => $overallSplits,
            ],
            'Conference' => [
                'label' => $groupLabels['Conference'],
                'totals' => $formatRecord($conf),
                'splits' => $confSplits,
            ],
            'Conference Tournament' => [
                'label' => $groupLabels['Conference Tournament'],
                'totals' => $formatRecord($confTourn),
                'splits' => $confTournSplits,
            ],
            'Postseason' => [
                'label' => $groupLabels['Postseason'],
                'totals' => $formatRecord($postseason),
                'splits' => $postseasonSplits,
            ],
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

        $rows = $teamSeasons->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->matching('Teams.Sports', function ($q) use ($sportName) {
                return $q->where(['Sports.sport_name' => $sportName]);
            })
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();

        /** @var array<int,\App\Model\Entity\TeamSeason> $result */
        $result = array_values(array_filter($rows, static fn($row): bool => $row instanceof TeamSeason));

        return $result;
    }

    /**
     * Get team seasons for the public seasons index, filtered by sport name and gender.
     *
     * @param string $sport Sport name to filter by (e.g. 'Basketball'). Empty string skips filter.
     * @param string $gender Gender to filter by ('M', 'F', etc.). Empty string skips filter.
     * @return array<int,\App\Model\Entity\TeamSeason>
     */
    public function getPublicSeasonsList(string $sport = 'Basketball', string $gender = 'M'): array
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');
        $query = $teamSeasons->find()
            ->contain(['Teams' => ['Sports'], 'Seasons']);

        if ($sport !== '') {
            $query->matching('Teams.Sports', function ($q) use ($sport) {
                return $q->where(['Sports.sport_name' => $sport]);
            });
        }

        if ($gender !== '') {
            $query->matching('Teams', function ($q) use ($gender) {
                return $q->where(['Teams.gender' => $gender]);
            });
        }

        $rows = $query->orderByDesc('Seasons.start')->all()->toArray();

        /** @var array<int,\App\Model\Entity\TeamSeason> $result */
        $result = array_values(array_filter($rows, static fn($row): bool => $row instanceof TeamSeason));

        return $result;
    }

    /**
     * Calculate overall and conference win/loss stats for a set of team season IDs.
     *
     * @param array<int> $teamSeasonIds
     * @return array<int, array<string, int|float|null>>
     */
    public function calculateSeasonStats(array $teamSeasonIds): array
    {
        if (empty($teamSeasonIds)) {
            return [];
        }

        $gamesTable = TableRegistry::getTableLocator()->get('Games');
        $query = $gamesTable->find();

        $rawStats = $query
            ->select([
                'team_season_id' => 'Games.team_season_id',
                'overall_wins' => $query->newExpr(
                    "SUM(CASE WHEN Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'overall_losses' => $query->newExpr(
                    "SUM(CASE WHEN Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
                'conf_wins' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'conf_losses' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
            ])
            ->where(['Games.team_season_id IN' => $teamSeasonIds])
            ->leftJoinWith('GameTypes')
            ->groupBy(['Games.team_season_id'])
            ->enableHydration(false)
            ->toArray();

        $stats = [];
        foreach ($rawStats as $row) {
            if (is_array($row)) {
                $id = (int)($row['team_season_id'] ?? 0);
                $ow = (int)($row['overall_wins'] ?? 0);
                $ol = (int)($row['overall_losses'] ?? 0);
                $cw = (int)($row['conf_wins'] ?? 0);
                $cl = (int)($row['conf_losses'] ?? 0);
            } else {
                $id = (int)($row->get('team_season_id') ?? 0);
                $ow = (int)($row->get('overall_wins') ?? 0);
                $ol = (int)($row->get('overall_losses') ?? 0);
                $cw = (int)($row->get('conf_wins') ?? 0);
                $cl = (int)($row->get('conf_losses') ?? 0);
            }
            if ($id <= 0) {
                continue;
            }
            $overallTotal = $ow + $ol;
            $confTotal = $cw + $cl;

            $stats[$id] = [
                'overall_wins' => $ow,
                'overall_losses' => $ol,
                'overall_pct' => $overallTotal > 0 ? round($ow / $overallTotal, 3) : null,
                'conf_wins' => $cw,
                'conf_losses' => $cl,
                'conf_pct' => $confTotal > 0 ? round($cw / $confTotal, 3) : null,
            ];
        }

        return $stats;
    }
}
