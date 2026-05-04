<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use Burzum\CakeServiceLayer\Service\ServiceAwareTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use DateTimeInterface;

/**
 * GameService
 *
 * Service for game-related business logic including:
 * - Loading games with associations
 * - Managing EAV metadata
 * - Retrieving formatted lists for forms
 * - Inline entity creation
 * - Score calculations and validations
 */
class GameService
{
    use LocatorAwareTrait;
    use ServiceAwareTrait;

    /**
     * SportConfigService instance
     *
     * @var \App\Service\SportConfigService|null
     */
    protected ?SportConfigService $sportConfigService = null;

    /**
     * Constructor
     *
     * @param \App\Service\SportConfigService|null $sportConfigService Sport config service
     */
    public function __construct(?SportConfigService $sportConfigService = null)
    {
        $this->sportConfigService = $sportConfigService ?? $this->loadService('SportConfig', [], false);
    }

    /**
     * Get a game with full associations for display/edit
     *
     * @param int $gameId Game ID
     * @return \App\Model\Entity\Game
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getGameWithAssociations(int $gameId): Game
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Entity\Game $game */
        $game = $gamesTable->find()
            ->contain([
                'TeamSeason' => ['Teams' => ['Sports'], 'Seasons'],
                'GameTypes',
                'Opponents',
                'Sites' => ['Places'],
                'Places',
            ])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        return $game;
    }

    /**
     * Load existing EAV values for a game as a flat array
     *
     * @param int $gameId Game ID
     * @return array EAV key-value pairs
     */
    public function loadGameEavValues(int $gameId): array
    {
        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');

        $rows = $gameEavTable->find()
            ->where(['game_id' => $gameId])
            ->all();

        $values = [];
        foreach ($rows as $row) {
            $values[$row->key] = $row->value;
        }

        return $values;
    }

    /**
     * Get EAV metadata for a game or team season
     *
     * Returns sport configuration, EAV template, and existing values if editing
     *
     * @param int|null $gameId Game ID (optional, for editing)
     * @param int|null $teamSeasonId Team Season ID (optional, for creating)
     * @return array Metadata with keys: sportId, sportName, configs, eavTemplate, values
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getGameEavMetadata(?int $gameId = null, ?int $teamSeasonId = null): array
    {
        $sportId = null;
        $sportName = null;
        $existingValues = [];
        $game = null;

        // Load from game if provided
        if ($gameId) {
            $game = $this->getGameWithAssociations($gameId);
            $teamSeasonId = (int)($game->get('team_season_id') ?? 0);

            /** @var \App\Model\Entity\TeamSeason|null $teamSeasonAssoc */
            $teamSeasonAssoc = $game->get('team_season');
            if ($teamSeasonAssoc && $teamSeasonAssoc->team && $teamSeasonAssoc->team->sport) {
                $sportId = $teamSeasonAssoc->team->sport->id;
                $sportName = $teamSeasonAssoc->team->sport->sport_name;
            }

            $existingValues = $this->loadGameEavValues($gameId);
        }

        // Load sport from team season if not yet resolved
        if (!$sportId && $teamSeasonId) {
            /** @var \App\Model\Entity\TeamSeason $teamSeason */
            $teamSeason = $this->fetchTable('TeamSeasons')->find()
                ->contain(['Teams' => ['Sports']])
                ->where(['TeamSeasons.id' => $teamSeasonId])
                ->firstOrFail();

            if ($teamSeason->team && $teamSeason->team->sport) {
                $sportId = $teamSeason->team->sport->id;
                $sportName = $teamSeason->team->sport->sport_name;
            }
        }

        if (!$sportId) {
            throw new RecordNotFoundException('Cannot determine sport from provided game or team season');
        }

        // Get sport configs
        /** @var \App\Model\Table\SportConfigsTable $sportConfigsTable */
        $sportConfigsTable = $this->fetchTable('SportConfigs');
        $configs = $sportConfigsTable->getFormattedConfigsForSport($sportId);

        // Get EAV template
        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');
        $gameEavTable->setSportConfigService($this->sportConfigService);

        $periods = '2';
        $overtime = '0';
        if ($game) {
            $periods = (string)($game->get('periods') ?: '2');
            $overtime = (string)($game->get('ot') ?: '0');
        }

        $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);

        return [
            'sportId' => $sportId,
            'sportName' => $sportName,
            'configs' => $configs,
            'eavTemplate' => $eavTemplate,
            'values' => $existingValues,
        ];
    }

    /**
     * Get formatted lists for game form (opponents, sites, places, game types)
     *
     * @param int|null $placeId Optional place ID to filter sites
     * @return array Associative array with keys: opponents, sites, places, gameTypes
     */
    public function getFormLists(?int $placeId = null): array
    {
        $opponents = (new OpponentService())->getOpponentsList();
        $sites = (new SiteService())->getSitesList($placeId);
        $places = (new PlaceService())->getPlacesList();
        $gameTypes = (new GameTypeService())->getGameTypesList();

        return compact('opponents', 'sites', 'places', 'gameTypes');
    }

    /**
     * Save a game with EAV values
     *
     * @param \App\Model\Entity\Game $game Game entity
     * @param array $eavData EAV key-value pairs
     * @return bool Success status
     */
    public function saveGameWithEav(Game $game, array $eavData): bool
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');

        // Save game first
        if (!$gamesTable->save($game)) {
            return false;
        }

        // Save EAV values
        $gameId = $game->id;
        foreach ($eavData as $key => $value) {
            // Skip empty values
            if ($value === '' || $value === null) {
                continue;
            }

            // Find existing or create new
            $existing = $gameEavTable->find()
                ->where(['game_id' => $gameId, 'eav_key' => $key])
                ->first();

            if ($existing) {
                $existing->eav_value = $value;
                $gameEavTable->save($existing);
            } else {
                $newEav = $gameEavTable->newEntity([
                    'game_id' => $gameId,
                    'eav_key' => $key,
                    'eav_value' => $value,
                ]);
                $gameEavTable->save($newEav);
            }
        }

        return true;
    }

    /**
     * Delete a game and its associated EAV values
     *
     * @param int $gameId Game ID
     * @return bool Success status
     */
    public function deleteGame(int $gameId): bool
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');

        // Delete EAV values first
        $gameEavTable->deleteAll(['game_id' => $gameId]);

        // Delete game
        $game = $gamesTable->get($gameId);

        return $gamesTable->delete($game);
    }

    /**
     * Get sites filtered by place ID
     *
     * @param int $placeId Place ID
     * @return array Array of sites with id and name
     */
    public function getSitesByPlace(int $placeId): array
    {
        return (new SiteService())->getSitesByPlace($placeId);
    }

    /**
     * Normalize inline creation payloads for associated entities
     *
     * Creates Place, Site, Opponent, or GameType entities from form data
     * and updates the data array with the new IDs
     *
     * @param array $data Request data (modified by reference)
     * @return void
     */
    public function normalizeAssociatedInlineCreate(array &$data): void
    {
        // New place
        if (!empty($data['new_place']['place_country'])) {
            $place = (new PlaceService())->createPlace([
                'place_country' => $data['new_place']['place_country'] ?? null,
                'place_city' => $data['new_place']['place_city'] ?? null,
                'place_state' => $data['new_place']['place_state'] ?? null,
            ]);
            if ($place) {
                $data['place_id'] = $place->get('id');
            }
        }

        // New site (requires place_id)
        if (!empty($data['new_site']['site_name'])) {
            $site = (new SiteService())->createSite([
                'site_name' => $data['new_site']['site_name'] ?? null,
                'place_id' => $data['place_id'] ?? null,
            ]);
            if ($site) {
                $data['site_id'] = $site->get('id');
            }
        }

        // New opponent
        if (!empty($data['new_opponent']['opponent_name'])) {
            $opp = (new OpponentService())->createOpponent([
                'opponent_name' => $data['new_opponent']['opponent_name'] ?? null,
                'place_id' => $data['place_id'] ?? null,
            ]);
            if ($opp) {
                $data['opponent_id'] = $opp->get('id');
            }
        }

        // New game type
        if (!empty($data['new_game_type']['game_type_name'])) {
            $gt = (new GameTypeService())->createGameType([
                'game_type_name' => $data['new_game_type']['game_type_name'] ?? null,
                'post' => $data['new_game_type']['post'] ?? 0,
                'conf' => $data['new_game_type']['conf'] ?? 0,
                'abr' => $data['new_game_type']['abr'] ?? null,
            ]);
            if ($gt) {
                $data['game_type_id'] = $gt->get('id');
            }
        }
    }

    /**
     * Auto-calculate W/L based on game scores
     *
     * @param array $data Game data
     * @return array Modified data with W/L set
     */
    public function calculateWinLoss(array $data): array
    {
        $teamScore = isset($data['pts_mur']) ? (int)$data['pts_mur'] : 0;
        $oppScore = isset($data['pts_opp']) ? (int)$data['pts_opp'] : 0;

        // Only calculate if both scores are present
        if ($teamScore > 0 || $oppScore > 0) {
            if ($teamScore > $oppScore) {
                $data['w'] = 1;
                $data['l'] = 0;
            } elseif ($teamScore < $oppScore) {
                $data['w'] = 0;
                $data['l'] = 1;
            } else {
                // Tie game
                $data['w'] = 1;
                $data['l'] = 1;
            }
        }

        return $data;
    }

    /**
     * Return a display result flag for a game: 'W', 'L', or 'T' when determinable.
     * Prefers explicit score totals, falls back to stored flags (`w`,`l`).
     *
     * @param \App\Model\Entity\Game $game Game entity
     * @return string|null
     */
    public function getResultFlag(Game $game): ?string
    {
        if ($game->pts_mur !== null && $game->pts_opp !== null) {
            if ($game->pts_mur > $game->pts_opp) {
                return 'W';
            }
            if ($game->pts_mur < $game->pts_opp) {
                return 'L';
            }

            return 'T';
        }

        if (!empty($game->w) && (int)$game->w === 1 && (empty($game->l) || (int)$game->l === 0)) {
            return 'W';
        }
        if (!empty($game->l) && (int)$game->l === 1 && (empty($game->w) || (int)$game->w === 0)) {
            return 'L';
        }
        if (!empty($game->w) && (int)$game->w === 1 && !empty($game->l) && (int)$game->l === 1) {
            return 'T';
        }

        return null;
    }

    /**
     * Get a human-friendly place name for a game.
     *
     * @param \App\Model\Entity\Game $game
     * @return string|null
     */
    public function getPlaceName(Game $game): ?string
    {
        if (empty($game->place)) {
            return null;
        }

        return $game->place->place_city ?? null;
    }

    /**
     * Get the place state for a game.
     *
     * @param \App\Model\Entity\Game $game
     * @return string|null
     */
    public function getPlaceState(Game $game): ?string
    {
        if (empty($game->place)) {
            return null;
        }

        return $game->place->place_state ?? null;
    }

    /**
     * Get the site name for a game if available.
     *
     * @param \App\Model\Entity\Game $game
     * @return string|null
     */
    public function getSiteName(Game $game): ?string
    {
        if (empty($game->site)) {
            return null;
        }

        return $game->site->site_name ?? null;
    }

    /**
     * Save game EAV data from request data
     *
     * @param int $gameId Game ID
     * @param array $data Request data containing EAV fields
     * @return void
     */
    public function saveGameEavFromRequest(int $gameId, array $data): void
    {
        // Get sport information for this game
        /** @var \App\Model\Entity\Game $game */
        $game = $this->fetchTable('Games')->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']]])
            ->where(['Games.id' => $gameId])
            ->first();

        /** @var \App\Model\Entity\TeamSeason|null $ts */
        $ts = $game->team_season;
        $sportId = $ts?->team->sport->id ?? null;

        if ($sportId) {
            /** @var \App\Model\Table\GameEavTable $gameEavTable */
            $gameEavTable = $this->fetchTable('GameEav');
            $gameEavTable->setSportConfigService($this->sportConfigService);

            // Pass game's periods and overtime values to generate appropriate EAV fields
            $periods = (string)($game->get('periods') ?: '2');
            $overtime = (string)($game->get('ot') ?: '0');
            $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);

            // Remove stale period/overtime EAV keys no longer in the template
            $validKeys = array_keys($eavTemplate);
            $existingValues = $this->loadGameEavValues($gameId);
            foreach (array_keys($existingValues) as $existingKey) {
                if (
                    !in_array($existingKey, $validKeys, true)
                    && (str_starts_with($existingKey, 'period_') || str_starts_with($existingKey, 'overtime_'))
                ) {
                    $gameEavTable->deleteAttribute($gameId, $existingKey);
                }
            }

            // Save all EAV fields from the template
            foreach ($eavTemplate as $key => $fieldConfig) {
                $value = $data[$key] ?? null;
                if ($value !== null && $value !== '') {
                    $this->upsertGameEav($gameId, $key, (string)$value);
                }
            }
        } else {
            // Fallback to traditional period/official handling if no sport is found
            $this->saveTraditionalEavFromRequest($gameId, $data);
        }
    }

    /**
     * Fallback method for saving traditional EAV data when sport config is unavailable
     *
     * @param int $gameId Game ID
     * @param array $data Request data
     * @return void
     */
    private function saveTraditionalEavFromRequest(int $gameId, array $data): void
    {
        $periods = (int)($data['periods'] ?? 0);
        if ($periods > 0) {
            for ($i = 1; $i <= $periods; $i++) {
                $mur = $data['period_' . $i . '_mur'] ?? null;
                $opp = $data['period_' . $i . '_opp'] ?? null;
                if ($mur !== null && $mur !== '') {
                    $this->upsertGameEav($gameId, 'period_' . $i . '_mur', (string)$mur);
                }
                if ($opp !== null && $opp !== '') {
                    $this->upsertGameEav($gameId, 'period_' . $i . '_opp', (string)$opp);
                }
            }
        }

        for ($j = 1; $j <= 3; $j++) {
            $name = trim((string)($data['official_' . $j] ?? ''));
            if ($name !== '') {
                $this->upsertGameEav($gameId, 'official_' . $j, $name);
            }
        }
    }

    /**
     * Upsert a single EAV key/value for a game
     *
     * @param int $gameId Game id
     * @param string $key Attribute key
     * @param string $value Attribute value
     * @return void
     */
    private function upsertGameEav(int $gameId, string $key, string $value): void
    {
        /** @var \App\Model\Table\GameEavTable $table */
        $table = $this->fetchTable('GameEav');
        $entity = $table->find()->where(['game_id' => $gameId, 'key' => $key])->first();
        if ($entity) {
            $entity->set('value', $value);
        } else {
            $entity = $table->newEntity(['game_id' => $gameId, 'key' => $key, 'value' => $value]);
        }
        $table->save($entity);
    }

    /**
     * Build DataTables result set for Games listing (server-side processing)
     *
     * @param array $params Parameters: start, length, searchValue, teamSeasonId, searchBuilder
     * @return array{recordsTotal:int,recordsFiltered:int,data:array<int,array<string,mixed>>}
     */
    public function buildGamesDataTable(array $params): array
    {
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 25);
        $searchValue = (string)($params['searchValue'] ?? '');
        $teamSeasonId = $params['teamSeasonId'] ?? null;
        $searchBuilder = $params['searchBuilder'] ?? null;

        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');
        $query = $gamesTable->find()
            ->contain([
                'TeamSeason' => ['Teams' => ['Sports'], 'Seasons'],
                'GameTypes', 'Opponents', 'Places',
            ]);

        if ($teamSeasonId) {
            $query->where(['Games.team_season_id' => $teamSeasonId]);
        }

        // Apply SearchBuilder criteria if present
        if (!empty($searchBuilder['criteria'])) {
            $this->applySearchBuilderCriteria($query, $searchBuilder['criteria'], $searchBuilder['logic'] ?? 'AND');
        }

        // Global search (only if no SearchBuilder criteria)
        if ($searchValue !== '' && empty($searchBuilder['criteria'])) {
            $query->where([
                'OR' => [
                    'Games.game_date LIKE' => '%' . $searchValue . '%',
                    'Teams.team_name LIKE' => '%' . $searchValue . '%',
                    'Opponents.opponent_name LIKE' => '%' . $searchValue . '%',
                    'GameTypes.game_type_name LIKE' => '%' . $searchValue . '%',
                    'Places.place_country LIKE' => '%' . $searchValue . '%',
                    'Places.place_city LIKE' => '%' . $searchValue . '%',
                    'Places.place_state LIKE' => '%' . $searchValue . '%',
                ],
            ]);
        }

        $recordsTotal = $gamesTable->find()->count();
        $recordsFiltered = $query->count();

        $query->limit($length)->offset($start)->orderByDesc('Games.game_date');
        $games = $query->all();

        $hrnMap = [1 => 'H', 2 => 'R', 3 => 'N'];
        $data = [];
        foreach ($games as $game) {
            /** @var \App\Model\Entity\Game $game */
            $teamName = '';
            $seasonRange = '';
            if (!empty($game->team_season) && !empty($game->team_season->team)) {
                $teamName = $game->team_season->team->team_name ?? '';
            }
            if (!empty($game->team_season) && !empty($game->team_season->season)) {
                $seasonRange = $game->team_season->season->start . '-' . $game->team_season->season->end;
            }
            $teamDisplay = $teamName . ($seasonRange ? ' (' . $seasonRange . ')' : '');
            if (!empty($game->mur_rk)) {
                $teamDisplay .= '<div><span class="badge bg-secondary">#' . h($game->mur_rk) . '</span></div>';
            }

            $oppName = '-';
            if (!empty($game->opponent) && !empty($game->opponent->opponent_name)) {
                $oppName = $game->opponent->opponent_name;
            }
            if (!empty($game->opp_rk)) {
                $oppName .= ' (#' . $game->opp_rk . ')';
            }

            $placeDisplay = '-';
            if (!empty($game->place)) {
                $placeDisplay = ($game->place->place_city ?? '');
                if (!empty($game->place->place_state)) {
                    $placeDisplay .= ', ' . $game->place->place_state;
                }
            }

            $result = '';
            if ($game->pts_mur !== null && $game->pts_opp !== null) {
                $result = $game->pts_mur > $game->pts_opp ? 'W' : ($game->pts_mur < $game->pts_opp ? 'L' : 'T');
            }

            $data[] = [
                'checkbox' => '<input type="checkbox" name="game_ids[]" value="' . $game->id .
                    '" class="game-checkbox" aria-label="Select game #' . $game->id . '">',
                'game_date' => $game->game_date
                    ? ($game->game_date instanceof Date
                        ? $game->game_date->i18nFormat('yyyy-MM-dd')
                        : ($game->game_date instanceof DateTimeInterface
                            ? $game->game_date->format('Y-m-d')
                            : (string)$game->game_date))
                    : '',
                'team_season' => $teamDisplay,
                'hrn' => $hrnMap[$game->hrn] ?? '-',
                'opponent' => $oppName,
                'game_type' => !empty($game->game_type) ? ($game->game_type->game_type_name ?? '-') : '-',
                'place' => $placeDisplay,
                'score' => '<a href="/admin/games/view/' . $game->id . '" class="text-decoration-none">' .
                    h(($game->pts_mur ?? '') . ' - ' . ($game->pts_opp ?? '')) . '</a>',
                'place_state' => !empty($game->place) ? ($game->place->place_state ?? '') : '',
                'mur_pts' => $game->pts_mur ?? '',
                'opp_pts' => $game->pts_opp ?? '',
                'mur_rk' => $game->mur_rk ?? '',
                'opp_rk' => $game->opp_rk ?? '',
                'result' => $result,
                'conf' => !empty($game->game_type) ? ($game->game_type->conf ?? '') : '',
                'post' => !empty($game->game_type) ? ($game->game_type->post ?? '') : '',
            ];
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    /**
     * Apply SearchBuilder criteria to a Games query (moved from controller)
     *
     * @param \Cake\ORM\Query $query Query reference
     * @param array $criteria Criteria array
     * @param string $logic Logic operator AND/OR
     * @return void
     */
    public function applySearchBuilderCriteria(Query $query, array $criteria, string $logic = 'AND'): void
    {
        $conditions = [];
        $hrnMap = ['H' => 1, 'R' => 2, 'N' => 3];

        foreach ($criteria as $criterion) {
            if (isset($criterion['criteria'])) { // nested group
                $subQuery = $this->fetchTable('Games')->find();
                $this->applySearchBuilderCriteria($subQuery, $criterion['criteria'], $criterion['logic'] ?? 'AND');
                $subConditions = $subQuery->clause('where');
                if ($subConditions) {
                    $conditions[] = $subConditions;
                }
                continue;
            }

            $origData = $criterion['origData'] ?? $criterion['data'] ?? null;
            $condition = $criterion['condition'] ?? '=';
            $value1 = $criterion['value1'] ?? $criterion['value'] ?? '';
            $value2 = $criterion['value2'] ?? '';

            $field = match ($origData) {
                '1', 'game_date' => 'Games.game_date',
                '2', 'team_season' => 'Teams.team_name',
                '3', 'hrn' => 'Games.hrn',
                '4', 'opponent' => 'Opponents.opponent_name',
                '5', 'game_type' => 'GameTypes.game_type_name',
                '6', 'place' => 'Places.place_city',
                '8', 'place_state' => 'Places.place_state',
                '9', 'mur_pts' => 'Games.pts_mur',
                '10', 'opp_pts' => 'Games.pts_opp',
                '11', 'mur_rk' => 'Games.mur_rk',
                '12', 'opp_rk' => 'Games.opp_rk',
                '13', 'result' => null,
                '14', 'conf' => 'GameTypes.conf',
                '15', 'post' => 'GameTypes.post',
                default => null,
            };

            // Computed result (W/L/T)
            if ($origData === '13' || $origData === 'result') {
                if ($value1 === 'W') {
                    $conditions[] = [
                        'Games.pts_mur > Games.pts_opp',
                    ];
                } elseif ($value1 === 'L') {
                    $conditions[] = [
                        'Games.pts_mur < Games.pts_opp',
                    ];
                } elseif ($value1 === 'T') {
                    $conditions[] = [
                        'Games.pts_mur = Games.pts_opp',
                    ];
                }
                continue;
            }

            if (!$field) {
                continue;
            }

            if ($field === 'Games.hrn' && isset($hrnMap[$value1])) {
                $value1 = $hrnMap[$value1];
            }

            $cond = match ($condition) {
                '=' => [$field => $value1],
                '!=' => [$field . ' !=' => $value1],
                'contains' => [$field . ' LIKE' => '%' . $value1 . '%'],
                '!contains' => [$field . ' NOT LIKE' => '%' . $value1 . '%'],
                'starts' => [$field . ' LIKE' => $value1 . '%'],
                '!starts' => [$field . ' NOT LIKE' => $value1 . '%'],
                'ends' => [$field . ' LIKE' => '%' . $value1],
                '!ends' => [$field . ' NOT LIKE' => '%' . $value1],
                '>' => [$field . ' >' => $value1],
                '<' => [$field . ' <' => $value1],
                '>=' => [$field . ' >=' => $value1],
                '<=' => [$field . ' <=' => $value1],
                'between' => [$field . ' >=' => $value1, $field . ' <=' => $value2],
                '!between' => ['OR' => [$field . ' <' => $value1, $field . ' >' => $value2]],
                'null' => [$field . ' IS' => null],
                '!null' => [$field . ' IS NOT' => null],
                default => [$field . ' LIKE' => '%' . $value1 . '%'],
            };

            $conditions[] = $cond;
        }

        if ($conditions) {
            $query->where([$logic => $conditions]);
        }
    }

    /**
     * Bulk delete games and return metadata
     *
     * @param array $ids Game IDs
     * @return array{deleted:int,teamSeasonId:int|null}
     */
    public function bulkDeleteGames(array $ids): array
    {
        $ids = array_values(array_filter($ids, fn($v) => $v !== '' && $v !== null && ctype_digit((string)$v)));
        if (!$ids) {
            return ['deleted' => 0, 'teamSeasonId' => null];
        }

        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');
        $teamSeasonId = null;
        $first = $gamesTable->find()
            ->where(['id' => $ids[0]])
            ->first();

        $teamSeasonId = $first ? $first->get('team_season_id') : null;

        $deleted = 0;
        foreach ($ids as $id) {
            $entity = $gamesTable->find()
                ->where(['id' => $id])
                ->first();

            if ($entity && $gamesTable->delete($entity)) {
                $deleted++;
            }
        }

        return ['deleted' => $deleted, 'teamSeasonId' => $teamSeasonId];
    }

    /**
     * Get team season list (formatted) and sports list for form helpers.
     *
     * @return array{teamSeasonList:array<int,string>,sports:array<int,string>}
     */
    public function getTeamSeasonAndSportsLists(): array
    {
        $teamSeasonList = (new TeamSeasonService())->getTeamSeasonsDetailedList();
        $sports = (new SportService())->getSportsList();

        return compact('teamSeasonList', 'sports');
    }

    /**
     * Get a recent-games list suitable for select/autocomplete UIs.
     *
     * @param int $limit
     * @return array<int,array{id:int,label:string,team_season_id:int|null}>
     */
    public function getRecentGamesForSelect(int $limit = 200): array
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        $query = $gamesTable->find()
            ->contain(['TeamSeason' => ['Teams'], 'Opponents'])
            ->orderByDesc('Games.game_date')
            ->limit($limit);

        $results = [];
        foreach ($query->all() as $g) {
            /** @var \App\Model\Entity\Game $g */
            $results[] = [
                'id' => (int)$g->id,
                'team_season_id' => $g->team_season_id !== null ? (int)$g->team_season_id : null,
                'label' => $this->formatGameSelectLabel($g),
            ];
        }

        return $results;
    }

    /**
     * Search games for autocomplete/select UIs.
     *
     * @param string $query Search query (team/opponent)
     * @param int|null $teamSeasonId Optional team season filter
     * @param int $limit
     * @return array<int,array{id:int,label:string,team_season_id:int|null}>
     */
    public function searchGamesForSelect(string $query = '', ?int $teamSeasonId = null, int $limit = 25): array
    {
        $query = trim($query);
        $teamSeasonId = $teamSeasonId && $teamSeasonId > 0 ? $teamSeasonId : null;

        if ($query === '' && $teamSeasonId === null) {
            return [];
        }

        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        $q = $gamesTable->find()
            ->contain(['TeamSeason' => ['Teams'], 'Opponents'])
            ->orderByDesc('Games.game_date')
            ->limit($limit);

        if ($teamSeasonId !== null) {
            $q->where(['Games.team_season_id' => $teamSeasonId]);
        }

        if ($query !== '') {
            $like = '%' . str_replace('%', '\\%', $query) . '%';
            $q->where([
                'OR' => [
                    ['Opponents.opponent_name LIKE' => $like],
                    ['Teams.team_name LIKE' => $like],
                ],
            ]);
        }

        $results = [];
        foreach ($q->all() as $game) {
            /** @var \App\Model\Entity\Game $game */
            $results[] = [
                'id' => (int)$game->id,
                'team_season_id' => $game->team_season_id !== null ? (int)$game->team_season_id : null,
                'label' => $this->formatGameSelectLabel($game),
            ];
        }

        return $results;
    }

    /**
     * Resolve a display label for a game id used by context tagging.
     *
     * @param int $gameId
     */
    public function getGameTagDisplayLabel(int $gameId): string
    {
        if ($gameId <= 0) {
            return 'game';
        }

        /** @var \App\Model\Table\GamesTable $games */
        $games = $this->fetchTable('Games');
        $game = $games->find()
            ->contain(['Opponents'])
            ->where(['Games.id' => $gameId])
            ->first();

        if (!$game) {
            return 'game-' . $gameId;
        }

        $opponentName = '';
        if (!empty($game->opponent) && !empty($game->opponent->opponent_name)) {
            $opponentName = (string)$game->opponent->opponent_name;
        }

        return $this->formatGameTagLabel($game->game_date ?? null, $opponentName, (int)($game->hrn ?? 0), $gameId);
    }

    /**
     * Format a tag label from known game fields without reloading the entity.
     *
     * @param mixed $gameDate
     * @param string $opponentName
     * @param int $hrn
     * @param int $gameId
     */
    public function formatGameTagLabelFromRow(
        mixed $gameDate,
        string $opponentName,
        int $hrn,
        int $gameId,
    ): string {
        return $this->formatGameTagLabel($gameDate, $opponentName, $hrn, $gameId);
    }

    /**
     * Format a stable, human-readable label for game selects.
     *
     * @param \App\Model\Entity\Game $game
     */
    private function formatGameSelectLabel(Game $game): string
    {
        $teamName = $game->team_season->team->team_name ?? 'Team';
        $oppName = $game->opponent->opponent_name ?? 'Opponent';

        $date = '';
        if (!empty($game->game_date)) {
            if ($game->game_date instanceof Date) {
                $date = $game->game_date->i18nFormat('yyyy-MM-dd');
            } elseif ($game->game_date instanceof DateTimeInterface) {
                $date = $game->game_date->format('Y-m-d');
            } else {
                $date = (string)$game->game_date;
            }
        }

        $score = $game->pts_mur !== null && $game->pts_opp !== null
            ? " {$game->pts_mur}-{$game->pts_opp}"
            : '';

        $separator = match ((int)$game->hrn) {
            1 => ' Vs ',
            2 => ' @ ',
            3 => ' vs ',
            default => ' vs ',
        };

        $label = $teamName . $separator . $oppName;
        if ($date !== '') {
            $label .= ' (' . $date . ')';
        }
        $label .= $score;

        return $label;
    }

    /**
     * Format a recognizable label for a game tag.
     *
     * @param mixed $gameDate
     * @param string $opponentName
     * @param int $hrn
     * @param int $gameId
     */
    private function formatGameTagLabel(mixed $gameDate, string $opponentName, int $hrn, int $gameId): string
    {
        $date = '';
        if (!empty($gameDate)) {
            if ($gameDate instanceof Date) {
                $date = $gameDate->i18nFormat('yyyy-MM-dd');
            } elseif ($gameDate instanceof DateTimeInterface) {
                $date = $gameDate->format('Y-m-d');
            } else {
                $date = (string)$gameDate;
            }
        }

        $opp = trim($opponentName);
        if ($opp === '') {
            return $date !== '' ? $date . ' — Game #' . $gameId : 'Game #' . $gameId;
        }

        $separator = match ((int)$hrn) {
            2 => ' @ ',
            default => ' vs ',
        };

        if ($date === '') {
            return 'Game' . $separator . $opp;
        }

        return $date . $separator . $opp;
    }

    /**
     * Get games for a team season with optional ordering.
     *
     * @param int|null $teamSeasonId
     * @param string $direction
     * @return array<int,\App\Model\Entity\Game>
     */
    public function getGamesByTeamSeason(?int $teamSeasonId = null, string $direction = 'DESC'): array
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $query = $gamesTable->find()
            ->contain(['Opponents', 'Places', 'Sites', 'GameTypes'])
            ->orderBy(['Games.game_date' => $direction]);

        if ($teamSeasonId !== null) {
            $query->where(['Games.team_season_id' => $teamSeasonId]);
        }

        return $query->all()->toArray();
    }
}
