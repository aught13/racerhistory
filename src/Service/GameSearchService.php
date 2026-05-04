<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use DateTimeInterface;
use Throwable;

/**
 * GameSearchService
 *
 * Provides predefined basketball game searches, streak computation,
 * margin records, and series history for the public /games landing page.
 */
class GameSearchService
{
    use LocatorAwareTrait;

    /**
     * Base query that loads games with full associations for basketball (sport_id=1, gender=M).
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function baseQuery(): SelectQuery
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        return $gamesTable->find()
            ->contain([
                'Opponents',
                'Places',
                'GameTypes',
                'TeamSeason' => ['Teams', 'Seasons'],
            ])
            ->matching('TeamSeason.Teams', function ($q) {
                return $q->where([
                    'Teams.sport_id' => 1,
                    'Teams.gender' => 'M',
                ]);
            })
            ->where([
                'OR' => [
                    'Games.pts_mur IS NOT' => null,
                    'Games.w IS NOT' => null,
                ],
            ]);
    }

    /**
     * Ranked games: team or opponent has a ranking.
     *
     * @param string $filter Filter by 'all', 'team', or 'opponent'
     * @return array
     */
    public function rankedGames(string $filter = 'all'): array
    {
        $query = $this->baseQuery();

        if ($filter === 'team') {
            // Only games where Murray State is ranked
            $query->where(function ($exp) {
                return $exp->and([
                    $exp->isNotNull('Games.mur_rk'),
                    $exp->notEq('Games.mur_rk', ''),
                ]);
            });
        } elseif ($filter === 'opponent') {
            // Only games where opponent is ranked
            $query->where(function ($exp) {
                return $exp->and([
                    $exp->isNotNull('Games.opp_rk'),
                    $exp->notEq('Games.opp_rk', ''),
                ]);
            });
        } else {
            // Games where either team or opponent is ranked
            $query->where(function ($exp) {
                return $exp->or([
                    function ($and) {
                        return $and->and([
                            $and->isNotNull('Games.mur_rk'),
                            $and->notEq('Games.mur_rk', ''),
                        ]);
                    },
                    function ($and) {
                        return $and->and([
                            $and->isNotNull('Games.opp_rk'),
                            $and->notEq('Games.opp_rk', ''),
                        ]);
                    },
                ]);
            });
        }

        return $query
            ->orderByDesc('Games.game_date')
            ->all()
            ->toArray();
    }

    /**
     * All games (no additional filters beyond base query).
     *
     * @return array
     */
    public function allGames(): array
    {
        return $this->baseQuery()
            ->orderByDesc('Games.game_date')
            ->all()
            ->toArray();
    }

    /**
     * Overtime games.
     *
     * @return array
     */
    public function overtimeGames(): array
    {
        return $this->baseQuery()
            ->where(function ($exp) {
                return $exp->isNotNull('Games.ot')
                    ->notEq('Games.ot', '')
                    ->notEq('Games.ot', '0');
            })
            ->orderByDesc('Games.game_date')
            ->all()
            ->toArray();
    }

    /**
     * 100-point games (team or opponent scored 100+).
     *
     * @param string $filter
     * @return array
     */
    public function hundredPointGames(string $filter = 'all'): array
    {
        $query = $this->baseQuery();

        if ($filter === 'team') {
            $pointsFilter = $query->newExpr()->add('CAST(Games.pts_mur AS INTEGER) >= :min_points');
        } elseif ($filter === 'opponent') {
            $pointsFilter = $query->newExpr()->add('CAST(Games.pts_opp AS INTEGER) >= :min_points');
        } else {
            $pointsFilter = $query->newExpr()->or([
                'CAST(Games.pts_mur AS INTEGER) >= :min_points',
                'CAST(Games.pts_opp AS INTEGER) >= :min_points',
            ]);
        }

        return $query
            ->where($pointsFilter)
            ->bind(':min_points', 100, 'integer')
            ->orderByDesc('Games.game_date')
            ->all()
            ->toArray();
    }

    /**
     * Season/home/conf/conf-home openers.
     *
     * @param string $type One of: season, home, conf, conf_home
     * @return array
     */
    public function openers(string $type = 'season'): array
    {
        $query = $this->baseQuery();

        // Apply the opener filter to the candidate game set.
        switch ($type) {
            case 'home':
                $query->where(['Games.hrn' => 1]);
                break;
            case 'conf':
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
            case 'conf_home':
                $query->where(['Games.hrn' => 1]);
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
            // 'season' = first game of each team_season (no extra filter)
        }

        $games = $query
            ->orderByAsc('Games.game_date')
            ->all()
            ->toArray();

        $seenTeamSeasons = [];
        $results = [];

        foreach ($games as $game) {
            $teamSeasonId = (int)($game->team_season_id ?? 0);
            if ($teamSeasonId <= 0 || isset($seenTeamSeasons[$teamSeasonId])) {
                continue;
            }

            $seenTeamSeasons[$teamSeasonId] = true;
            $results[] = $game;
        }

        // Sort descending by date
        usort($results, function ($a, $b) {
            return strcmp((string)$b->game_date, (string)$a->game_date);
        });

        return $results;
    }

    /**
     * Compute streaks (winning or losing).
     *
     * @param string $resultType 'W' or 'L'
     * @param string $filter One of: overall, home, road, conf, conf_home, conf_road
     * @param int $limit Max streaks to return
     * @return array Each entry: ['length' => int, 'start_date' => string, 'end_date' => string, 'start_opponent' => string, 'end_opponent' => string, 'season' => string]
     */
    public function streaks(string $resultType = 'W', string $filter = 'overall', int $limit = 20): array
    {
        $query = $this->baseQuery();

        switch ($filter) {
            case 'home':
                $query->where(['Games.hrn' => 1]);
                break;
            case 'road':
                $query->where(['Games.hrn' => 2]);
                break;
            case 'conf':
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
            case 'conf_home':
                $query->where(['Games.hrn' => 1]);
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
            case 'conf_road':
                $query->where(['Games.hrn' => 2]);
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
        }

        $query->orderByAsc('Games.game_date');

        $games = $query->all()->toArray();

        // Walk through games to find consecutive streaks
        $streaks = [];
        $currentStreak = 0;
        $streakStart = null;
        $streakStartOpp = '';

        foreach ($games as $game) {
            $result = $this->getResult($game);
            if ($result === $resultType) {
                if ($currentStreak === 0) {
                    $streakStart = $game;
                    $streakStartOpp = $game->opponent->opponent_short ?? '?';
                }
                $currentStreak++;
            } else {
                if ($currentStreak > 0) {
                    // Save the streak that just ended
                    // $game is the game that ended the streak (loss for winning streak, win for losing streak)
                    $streaks[] = [
                        'length' => $currentStreak,
                        'start_date' => $streakStart->game_date ? $streakStart->game_date->format('Y-m-d') : '',
                        'end_date' => $game->game_date ? $game->game_date->format('Y-m-d') : '',
                        'start_opponent' => $streakStartOpp,
                        'end_opponent' => $game->opponent->opponent_short ?? '?',
                    ];
                }
                $currentStreak = 0;
                $streakStart = null;
            }
        }

        // Don't forget a streak that extends to the last game
        if ($currentStreak > 0 && $streakStart) {
            $lastGame = end($games);
            $streaks[] = [
                'length' => $currentStreak,
                'start_date' => $streakStart->game_date ? $streakStart->game_date->format('Y-m-d') : '',
                'end_date' => $lastGame->game_date ? $lastGame->game_date->format('Y-m-d') : '',
                'start_opponent' => $streakStartOpp,
                'end_opponent' => $lastGame->opponent->opponent_short ?? '?',
                'active' => true,
            ];
        }

        // Separate active streaks from ended ones
        $activeStreaks = array_filter($streaks, function ($s) {
            return !empty($s['active']);
        });
        $endedStreaks = array_filter($streaks, function ($s) {
            return empty($s['active']);
        });

        // Sort ended streaks: by length descending, then by end_date descending (most recent)
        usort($endedStreaks, function ($a, $b) {
            $lengthCmp = $b['length'] <=> $a['length'];
            if ($lengthCmp !== 0) {
                return $lengthCmp;
            }
            // Same length, sort by end_date descending (most recent)
            return $b['end_date'] <=> $a['end_date'];
        });

        // Take top 20 unique lengths, max 100 total rows
        $groupedByLength = [];
        $lengthRanks = [];
        $currentLengthRank = 0;
        $totalRows = 0;

        foreach ($endedStreaks as $streak) {
            $length = $streak['length'];
            if (!isset($lengthRanks[$length])) {
                $currentLengthRank++;
                $lengthRanks[$length] = $currentLengthRank;
            }

            // Stop if we've reached 20 unique lengths or 100 total rows
            if ($currentLengthRank > $limit || $totalRows >= 100) {
                break;
            }

            $streak['rank'] = $lengthRanks[$length];
            $groupedByLength[] = $streak;
            $totalRows++;
        }

        // Add active streaks at the beginning with rank 0
        foreach ($activeStreaks as $streak) {
            $streak['rank'] = 0;
            array_unshift($groupedByLength, $streak);
        }

        return $groupedByLength;
    }

    /**
     * Margin records (biggest wins or losses).
     *
     * @param string $type 'win' or 'loss'
     * @param string $filter One of: overall, home, road, neutral, conf, conf_home, conf_road
     * @param int $limit Max records to return
     * @return array
     */
    public function margins(string $type = 'win', string $filter = 'overall', int $limit = 20): array
    {
        $query = $this->baseQuery()
            ->where([
                'Games.pts_mur IS NOT' => null,
                'Games.pts_opp IS NOT' => null,
            ]);

        // Filter by location
        switch ($filter) {
            case 'home':
                $query->where(['Games.hrn' => 1]);
                break;
            case 'road':
                $query->where(['Games.hrn' => 2]);
                break;
            case 'neutral':
                $query->where(['Games.hrn' => 3]);
                break;
            case 'conf':
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
            case 'conf_home':
                $query->where(['Games.hrn' => 1]);
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
            case 'conf_road':
                $query->where(['Games.hrn' => 2]);
                $query->innerJoinWith('GameTypes', function ($q) {
                    return $q->where(['GameTypes.conf' => true]);
                });
                break;
        }

        // Filter wins vs losses
        if ($type === 'win') {
            $query->whereNotNull('Games.w')
                ->where(function ($exp) {
                    return $exp->notEq('Games.w', '0');
                });
        } else {
            $query->whereNotNull('Games.l')
                ->where(function ($exp) {
                    return $exp->notEq('Games.l', '0');
                });
        }

        $games = $query->all()->toArray();

        // Calculate margin and sort
        foreach ($games as $g) {
            $ptsMur = (int)$g->pts_mur;
            $ptsOpp = (int)$g->pts_opp;
            $g->set('margin', abs($ptsMur - $ptsOpp));
        }

        usort($games, function ($a, $b) {
            $marginCmp = (int)($b->margin ?? 0) <=> (int)($a->margin ?? 0);
            if ($marginCmp !== 0) {
                return $marginCmp;
            }

            $aDate = $this->marginSortDateKey($a->game_date ?? null);
            $bDate = $this->marginSortDateKey($b->game_date ?? null);

            // For tied margins, prefer the most recent game.
            return $bDate <=> $aDate;
        });

        $rankedGames = [];
        $marginRanks = [];
        $currentMarginRank = 0;
        $totalRows = 0;

        foreach ($games as $game) {
            $margin = (int)($game->margin ?? 0);
            if (!isset($marginRanks[$margin])) {
                $currentMarginRank++;
                $marginRanks[$margin] = $currentMarginRank;
            }

            if ($currentMarginRank > $limit || $totalRows >= 100) {
                break;
            }

            $game->set('rank', $marginRanks[$margin]);
            $rankedGames[] = $game;
            $totalRows++;
        }

        return $rankedGames;
    }

    /**
     * Normalize margin date values to YYYY-MM-DD for stable sorting.
     *
     * Handles date objects, ISO strings, and legacy m/d/yy strings.
     *
     * @param mixed $date
     * @return string
     */
    private function marginSortDateKey(mixed $date): string
    {
        if ($date === null) {
            return '';
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (is_object($date) && method_exists($date, 'format')) {
            try {
                return (string)$date->format('Y-m-d');
            } catch (Throwable $e) {
            }
        }

        $raw = trim((string)$date);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return $raw;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $raw, $m) === 1) {
            $month = (int)$m[1];
            $day = (int)$m[2];
            $year = (int)$m[3];

            if ($year < 100) {
                $currentTwoDigitYear = (int)date('y');
                $year += $year <= $currentTwoDigitYear ? 2000 : 1900;
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return $raw;
    }

    /**
     * Series history: overall record vs a specific opponent.
     *
     * @param int $opponentId Opponent ID
     * @return array{record: array, games: array}
     */
    public function seriesHistory(int $opponentId): array
    {
        $games = $this->baseQuery()
            ->where(['Games.opponent_id' => $opponentId])
            ->orderByDesc('Games.game_date')
            ->all()
            ->toArray();

        $record = [
            'wins' => 0,
            'losses' => 0,
            'overall' => '0-0',
            'home_wins' => 0,
            'home_losses' => 0,
            'home' => '0-0',
            'road_wins' => 0,
            'road_losses' => 0,
            'road' => '0-0',
            'neutral_wins' => 0,
            'neutral_losses' => 0,
            'neutral' => '0-0',
            'first_game' => null,
            'last_game' => null,
        ];

        foreach ($games as $g) {
            $result = $this->getResult($g);
            $hrn = (int)($g->hrn ?? 0);

            if ($result === 'W') {
                $record['wins']++;
                if ($hrn === 1) {
                    $record['home_wins']++;
                } elseif ($hrn === 2) {
                    $record['road_wins']++;
                } elseif ($hrn === 3) {
                    $record['neutral_wins']++;
                }
            } elseif ($result === 'L') {
                $record['losses']++;
                if ($hrn === 1) {
                    $record['home_losses']++;
                } elseif ($hrn === 2) {
                    $record['road_losses']++;
                } elseif ($hrn === 3) {
                    $record['neutral_losses']++;
                }
            }
        }

        $record['overall'] = $record['wins'] . '-' . $record['losses'];
        $record['home'] = $record['home_wins'] . '-' . $record['home_losses'];
        $record['road'] = $record['road_wins'] . '-' . $record['road_losses'];
        $record['neutral'] = $record['neutral_wins'] . '-' . $record['neutral_losses'];

        if (!empty($games)) {
            $record['last_game'] = $games[0]; // games ordered desc
            $record['first_game'] = end($games);
        }

        // Calculate last 10 games record
        $last10Wins = 0;
        $last10Losses = 0;
        $last10Games = array_slice($games, 0, 10);
        foreach ($last10Games as $g) {
            $result = $this->getResult($g);
            if ($result === 'W') {
                $last10Wins++;
            } elseif ($result === 'L') {
                $last10Losses++;
            }
        }
        $record['last10'] = $last10Wins . '-' . $last10Losses;

        // Calculate current streak
        $streakType = null;
        $streakCount = 0;
        foreach ($games as $g) {
            $result = $this->getResult($g);
            if ($result === null) {
                continue;
            }
            if ($streakType === null) {
                $streakType = $result;
                $streakCount = 1;
            } elseif ($streakType === $result) {
                $streakCount++;
            } else {
                break;
            }
        }
        $record['streak'] = $streakType ? $streakType . $streakCount : '-';
        $record['streak_count'] = $streakCount;
        $record['streak_type'] = $streakType;

        // Find biggest win and biggest loss
        $biggestWin = null;
        $biggestWinMargin = 0;
        $biggestLoss = null;
        $biggestLossMargin = 0;

        // Enrich games with computed display fields
        foreach ($games as $g) {
            $ptsMur = (int)($g->pts_mur ?? 0);
            $ptsOpp = (int)($g->pts_opp ?? 0);
            $margin = $ptsMur - $ptsOpp;
            $g->set('margin', abs($margin));
            $result = $this->getResult($g);
            $g->set('result_flag', $result);

            // Track biggest wins/losses
            if ($result === 'W' && $margin > $biggestWinMargin) {
                $biggestWinMargin = $margin;
                $biggestWin = $g;
            } elseif ($result === 'L' && abs($margin) > $biggestLossMargin) {
                $biggestLossMargin = abs($margin);
                $biggestLoss = $g;
            }
        }

        $record['biggest_win'] = $biggestWin;
        $record['biggest_win_margin'] = $biggestWinMargin;
        $record['biggest_loss'] = $biggestLoss;
        $record['biggest_loss_margin'] = $biggestLossMargin;

        return [
            'record' => $record,
            'games' => $games,
        ];
    }

    /**
     * Get all opponents for the search dropdown.
     *
     * @return array<int, string> id => name
     */
    public function getOpponentsList(): array
    {
        $gamesTable = $this->fetchTable('Games');
        $opponentsTable = $this->fetchTable('Opponents');

        // Only return opponents that have games
        $opponentIds = $gamesTable->find()
            ->select(['Games.opponent_id'])
            ->distinct(['Games.opponent_id'])
            ->matching('TeamSeason.Teams', function ($q) {
                return $q->where([
                    'Teams.sport_id' => 1,
                    'Teams.gender' => 'M',
                ]);
            })
            ->all()
            ->extract('opponent_id')
            ->toArray();

        if (empty($opponentIds)) {
            return [];
        }

        return $opponentsTable->find('list', keyField: 'id', valueField: 'opponent_name')
            ->where(['id IN' => $opponentIds])
            ->orderByAsc('opponent_name')
            ->all()
            ->toArray();
    }

    /**
     * Search opponents for the series DataTable list.
     *
     * Keeps the same criteria used by the existing series search:
     * opponent name and short abbreviation.
     *
     * @param string $query
     * @param int $start
     * @param int $length
     * @return array{rows: array<int, array<string,mixed>>, total: int, filtered: int}
     */
    public function searchSeriesOpponents(string $query, int $start = 0, int $length = 50): array
    {
        $gamesTable = $this->fetchTable('Games');

        $baseQuery = $gamesTable->find();
        $baseQuery
            ->select([
                'opponent_id' => 'Opponents.id',
                'opponent_name' => 'Opponents.opponent_name',
                'opponent_short' => 'Opponents.opponent_short',
                'games_count' => $baseQuery->func()->count('Games.id'),
            ])
            ->innerJoinWith('Opponents')
            ->innerJoinWith('TeamSeason.Teams', function (SelectQuery $q) {
                return $q->where([
                    'Teams.sport_id' => 1,
                    'Teams.gender' => 'M',
                ]);
            })
            ->groupBy([
                'Opponents.id',
                'Opponents.opponent_name',
                'Opponents.opponent_short',
            ]);

        $query = trim($query);
        if ($query !== '') {
            $baseQuery->where([
                'OR' => [
                    'Opponents.opponent_name LIKE' => "%{$query}%",
                    'Opponents.opponent_short LIKE' => "%{$query}%",
                ],
            ]);
        }

        $countQuery = clone $baseQuery;
        $filtered = count($countQuery->all()->toList());

        $totalQuery = $gamesTable->find()
            ->select(['opponent_id' => 'Games.opponent_id'])
            ->distinct(['Games.opponent_id'])
            ->innerJoinWith('TeamSeason.Teams', function (SelectQuery $q) {
                return $q->where([
                    'Teams.sport_id' => 1,
                    'Teams.gender' => 'M',
                ]);
            });
        $total = count($totalQuery->all()->toList());

        $rows = $baseQuery
            ->orderByAsc('Opponents.opponent_name')
            ->offset($start)
            ->limit($length)
            ->enableHydration(false)
            ->all()
            ->toArray();

        return [
            'rows' => $rows,
            'total' => $total,
            'filtered' => $query === '' ? $total : $filtered,
        ];
    }

    /**
     * Determine W/L result from a game entity.
     *
     * @param object $game
     * @return string|null 'W', 'L', or null
     */
    private function getResult(object $game): ?string
    {
        $w = (string)($game->w ?? '');
        if ($w !== '' && $w !== '0') {
            return 'W';
        }
        $l = (string)($game->l ?? '');
        if ($l !== '' && $l !== '0') {
            return 'L';
        }

        $ptsMur = (int)($game->pts_mur ?? 0);
        $ptsOpp = (int)($game->pts_opp ?? 0);
        if ($ptsMur > 0 && $ptsOpp > 0) {
            if ($ptsMur > $ptsOpp) {
                return 'W';
            }
            if ($ptsOpp > $ptsMur) {
                return 'L';
            }
        }

        return null;
    }

    /**
     * Format HRN value to display string.
     *
     * @param int|null $hrn
     * @return string
     */
    public static function hrnLabel(?int $hrn): string
    {
        return match ($hrn) {
            1 => 'H',
            2 => 'R',
            3 => 'N',
            default => '-',
        };
    }
}
