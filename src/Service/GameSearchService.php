<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;

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
        $streakSeason = '';

        foreach ($games as $game) {
            $result = $this->getResult($game);
            if ($result === $resultType) {
                if ($currentStreak === 0) {
                    $streakStart = $game;
                    $streakStartOpp = $game->opponent->opponent_name ?? '?';
                    $streakSeason = ($game->team_season->season->start ?? '') .
                        '-' . ($game->team_season->season->end ?? '');
                }
                $currentStreak++;
            } else {
                if ($currentStreak > 0) {
                    // Save the streak that just ended - the previous game was the last of the streak
                    $streaks[] = [
                        'length' => $currentStreak,
                        'start_date' => (string)($streakStart->game_date ?? ''),
                        'end_date' => (string)($game->game_date ?? ''),
                        'start_opponent' => $streakStartOpp,
                        'end_opponent' => $this->getPreviousOpponent($games, $game) ?? $streakStartOpp,
                        'season' => $streakSeason,
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
                'start_date' => (string)($streakStart->game_date ?? ''),
                'end_date' => (string)($lastGame->game_date ?? ''),
                'start_opponent' => $streakStartOpp,
                'end_opponent' => $lastGame->opponent->opponent_name ?? '?',
                'season' => $streakSeason,
                'active' => true,
            ];
        }

        // Sort by length descending
        usort($streaks, function ($a, $b) {
            return $b['length'] <=> $a['length'];
        });

        return array_slice($streaks, 0, $limit);
    }

    /**
     * Get the opponent name of the game before $currentGame in the array.
     *
     * @param array $games Ordered games array
     * @param object $currentGame The current game
     * @return string|null
     */
    private function getPreviousOpponent(array $games, object $currentGame): ?string
    {
        $prev = null;
        foreach ($games as $g) {
            if ($g->id === $currentGame->id) {
                return $prev ? ($prev->opponent->opponent_name ?? null) : null;
            }
            $prev = $g;
        }

        return null;
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
            return $b->margin <=> $a->margin;
        });

        return array_slice($games, 0, $limit);
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

        // Enrich games with computed display fields
        foreach ($games as $g) {
            $ptsMur = (int)($g->pts_mur ?? 0);
            $ptsOpp = (int)($g->pts_opp ?? 0);
            $g->set('margin', abs($ptsMur - $ptsOpp));
            $g->set('result_flag', $this->getResult($g));
        }

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
