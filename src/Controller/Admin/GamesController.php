<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Games Controller
 *
 * Manages games and associated values (game types, opponent, site/place) and
 * EAV attributes such as period scores and officials.
 *
 * @property \App\Model\Table\GamesTable $Games
 */
class GamesController extends AppController
{
    /**
     * SportConfigService instance for sport-aware configurations
     *
     * @var \App\Service\SportConfigService
     */
    protected \App\Service\SportConfigService $sportConfigService;

    /**
     * GameService instance for game business logic
     *
     * @var \App\Service\GameService
     */
    protected \App\Service\GameService $gameService;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->sportConfigService = new \App\Service\SportConfigService();
        $this->gameService = new \App\Service\GameService();
    }

    /**
     * Unified AJAX meta endpoint.
     * Accepts one of:
     *  - game_id: load existing game, infer team_season/sport, include existing EAV values
     *  - team_season_id: load sport meta without existing values
     * Returns JSON: { success, sportId, sportName, configs, eavTemplate, values }
     *
     * @return \Cake\Http\Response|null
     */
    public function ajaxGameEavMeta(): ?Response
    {
        $this->request->allowMethod(['get']);
        $gameId = (int)$this->request->getQuery('game_id');
        $teamSeasonId = (int)$this->request->getQuery('team_season_id');
        $payload = ['success' => false];

        try {
            $metadata = $this->gameService->getGameEavMetadata(
                $gameId ?: null,
                $teamSeasonId ?: null
            );

            $payload = [
                'success' => true,
                'sportId' => $metadata['sportId'],
                'sportName' => $metadata['sportName'],
                'configs' => $metadata['configs'],
                'eavTemplate' => $metadata['eavTemplate'],
                'values' => $metadata['values'],
            ];

            // If HTML fragment requested, render the server-side element and return HTML
            $format = $this->request->getQuery('format');
            if ($format === 'html') {
                // Prepare variables expected by the element
                $eavTemplate = $metadata['eavTemplate'];
                $eav = $metadata['values'];
                $legacyMappedEav = $metadata['values']; // element expects this variable optionally
                $sportName = $metadata['sportName'];

                $html = $this->viewBuilder()
                    ->setClassName('App\View\AppView')
                    ->build()
                    ->element(
                        'Admin/Games/sport_specific_fields',
                        compact('eavTemplate', 'eav', 'legacyMappedEav', 'sportName')
                    );

                return $this->response->withType('text/html')->withStringBody($html);
            }
        } catch (\Throwable $e) {
            $payload['error'] = 'Lookup failed';
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * AJAX endpoint to get sites filtered by place_id
     *
     * @return \Cake\Http\Response|null
     */
    public function ajaxSitesByPlace(): ?Response
    {
        $this->request->allowMethod(['get']);
        $placeId = (int)$this->request->getQuery('place_id');

        $sites = $this->gameService->getSitesByPlace($placeId);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['sites' => $sites]));
    }

    /**
     * List games with associations.
     */
    public function index(): void
    {
        // Check for team season filter context
        $teamSeasonId = $this->request->getQuery('team_season_id');
        if ($teamSeasonId) {
            $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);
            $this->set('teamSeason', $teamSeason);
        }

        $this->set('teamSeasonId', $teamSeasonId);
    }

    /**
     * AJAX endpoint for DataTables server-side processing.
     * Returns JSON data with pagination, search, and sorting.
     */
    public function ajaxList(): void
    {
        $this->request->allowMethod(['get', 'post']);
        $this->viewBuilder()->setClassName('Json')->disableAutoLayout();

        // DataTables parameters
        $draw = (int)$this->request->getData('draw', $this->request->getQuery('draw', 1));
        $start = (int)$this->request->getData('start', $this->request->getQuery('start', 0));
        $length = (int)$this->request->getData('length', $this->request->getQuery('length', 25));
        $searchValue = $this->request->getData('search.value', $this->request->getQuery('search.value', ''));
        $teamSeasonId = $this->request->getQuery('team_season_id');

        // SearchBuilder parameters (sent as searchBuilder object)
        $searchBuilder = $this->request->getData('searchBuilder', $this->request->getQuery('searchBuilder'));

        // Base query
        $query = $this->Games->find()
            ->contain([
                'TeamSeason' => ['Teams', 'Seasons'],
                'GameTypes',
                'Opponents',
                'Places',
            ]);

        // Optional filter by team season
        if ($teamSeasonId) {
            $query->where(['Games.team_season_id' => $teamSeasonId]);
        }

        // Apply SearchBuilder criteria if present
        if (!empty($searchBuilder['criteria'])) {
            $this->applySearchBuilderCriteria($query, $searchBuilder['criteria'], $searchBuilder['logic'] ?? 'AND');
        }

        // Global search across multiple fields (only if no SearchBuilder)
        if (!empty($searchValue) && empty($searchBuilder['criteria'])) {
            $query->where([
                'OR' => [
                    'Games.game_date LIKE' => '%' . $searchValue . '%',
                    'Teams.team_name LIKE' => '%' . $searchValue . '%',
                    'Opponents.opponent_name LIKE' => '%' . $searchValue . '%',
                    'GameTypes.game_type_name LIKE' => '%' . $searchValue . '%',
                    'Places.place_name LIKE' => '%' . $searchValue . '%',
                    'Places.place_state LIKE' => '%' . $searchValue . '%',
                ],
            ]);
        }

        // Get total count
        $recordsTotal = $this->Games->find()->count();
        $recordsFiltered = $query->count();

        // Apply pagination and ordering
        $query->limit($length)->offset($start)->orderByDesc('Games.game_date');

        // Fetch results
        $games = $query->all();

        // Format data for DataTables
        $hrnMap = [1 => 'H', 2 => 'R', 3 => 'N'];
        $data = [];
        foreach ($games as $game) {
            $teamName = $game->team_season->team->team_name ?? '';
            $seasonRange = isset($game->team_season->season)
                ? $game->team_season->season->start . '-' . $game->team_season->season->end
                : '';
            $teamDisplay = $teamName . ($seasonRange ? ' (' . $seasonRange . ')' : '');
            if (!empty($game->mur_rk)) {
                $teamDisplay .= '<div><span class="badge bg-secondary">#' . h($game->mur_rk) . '</span></div>';
            }

            $oppName = $game->opponent->opponent_name ?? '-';
            if (!empty($game->opp_rk)) {
                $oppName .= ' (#' . $game->opp_rk . ')';
            }

            $placeDisplay = '-';
            if (isset($game->place)) {
                $placeDisplay = ($game->place->place_name ?? '') .
                    (!empty($game->place->place_state) ? ', ' . $game->place->place_state : '');
            }

            // Determine win/loss
            $result = '';
            if ($game->pts_mur !== null && $game->pts_opp !== null) {
                $result = $game->pts_mur > $game->pts_opp ? 'W' : ($game->pts_mur < $game->pts_opp ? 'L' : 'T');
            }

            $data[] = [
                'checkbox' =>
                    '<input type="checkbox" name="game_ids[]" value="' .
                    $game->id .
                    '" class="game-checkbox" aria-label="Select game #' .
                    $game->id .
                    '">',
                'game_date' => $game->game_date ? $game->game_date->format('Y-m-d') : '',
                'team_season' => $teamDisplay,
                'hrn' => $hrnMap[$game->hrn] ?? '-',
                'opponent' => $oppName,
                'game_type' => $game->game_type->game_type_name ?? '-',
                'place' => $placeDisplay,
                'score' => '<a href="/admin/games/view/' . $game->id . '" class="text-decoration-none">' .
                    h(($game->pts_mur ?? '') . ' - ' . ($game->pts_opp ?? '')) . '</a>',
                // Hidden columns for SearchBuilder
                'place_state' => $game->place->place_state ?? '',
                'mur_pts' => $game->pts_mur ?? '',
                'opp_pts' => $game->pts_opp ?? '',
                'mur_rk' => $game->mur_rk ?? '',
                'opp_rk' => $game->opp_rk ?? '',
                'result' => $result,
                'conf' => $game->game_type->conf ?? '',
                'post' => $game->game_type->post ?? '',
            ];
        }

        $this->set([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
        $this->viewBuilder()->setOption('serialize', ['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /**
     * Apply SearchBuilder criteria to query
     *
     * @param \Cake\ORM\Query $query The query to modify
     * @param array $criteria SearchBuilder criteria array
     * @param string $logic Logic operator (AND/OR)
     * @return void
     */
    private function applySearchBuilderCriteria(\Cake\ORM\Query $query, array $criteria, string $logic = 'AND'): void
    {
        $conditions = [];
        $hrnMap = ['H' => 1, 'R' => 2, 'N' => 3];

        foreach ($criteria as $criterion) {
            // Handle nested groups
            if (isset($criterion['criteria'])) {
                $subQuery = $this->Games->find();
                $this->applySearchBuilderCriteria($subQuery, $criterion['criteria'], $criterion['logic'] ?? 'AND');
                $subConditions = $subQuery->clause('where');
                if ($subConditions) {
                    $conditions[] = $subConditions;
                }

                continue;
            }

            // Get criterion properties
            $origData = $criterion['origData'] ?? $criterion['data'] ?? null;
            $condition = $criterion['condition'] ?? '=';
            $value1 = $criterion['value1'] ?? $criterion['value'] ?? '';
            $value2 = $criterion['value2'] ?? '';

            // Map origData to database field
            $field = match ($origData) {
                '1', 'game_date' => 'Games.game_date',
                '2', 'team_season' => 'Teams.team_name',
                '3', 'hrn' => 'Games.hrn',
                '4', 'opponent' => 'Opponents.opponent_name',
                '5', 'game_type' => 'GameTypes.game_type_name',
                '6', 'place' => 'Places.place_name',
                '8', 'place_state' => 'Places.place_state',
                '9', 'mur_pts' => 'Games.pts_mur',
                '10', 'opp_pts' => 'Games.pts_opp',
                '11', 'mur_rk' => 'Games.mur_rk',
                '12', 'opp_rk' => 'Games.opp_rk',
                '13', 'result' => null, // Computed field, handle separately
                '14', 'conf' => 'GameTypes.conf',
                '15', 'post' => 'GameTypes.post',
                default => null,
            };

            // Handle computed 'result' field (W/L/T)
            if ($origData === '13' || $origData === 'result') {
                if ($value1 === 'W') {
                    $conditions[] = [
                        'Games.pts_mur >' => new \Cake\Database\Expression\IdentifierExpression('Games.pts_opp'),
                    ];
                } elseif ($value1 === 'L') {
                    $conditions[] = [
                        'Games.pts_mur <' => new \Cake\Database\Expression\IdentifierExpression('Games.pts_opp'),
                    ];
                } elseif ($value1 === 'T') {
                    $conditions[] = [
                        'Games.pts_mur' => new \Cake\Database\Expression\IdentifierExpression('Games.pts_opp'),
                    ];
                }

                continue;
            }

            if (!$field) {
                continue;
            }

            // Convert HRN display value to database value
            if ($field === 'Games.hrn' && isset($hrnMap[$value1])) {
                $value1 = $hrnMap[$value1];
            }

            // Build condition based on operator
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

        if (!empty($conditions)) {
            $query->where([$logic => $conditions]);
        }
    }

    /**
     * View a game with associations and EAV attributes.
     *
     * @param string $id Game ID
     */
    public function view(string $id): void
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->Games->find()
            ->contain([
                'TeamSeason' => ['Teams' => ['Sports'], 'Seasons'], 'GameTypes', 'Opponents',
                'Sites' => ['Places'], 'Places',
            ])
            ->where(['Games.id' => $id])
            ->firstOrFail();
        $eav = $this->loadGameEavArray((int)$id);

        // Initialize stat variables with defaults
        $teamBoxStats = [];
        $opponentBoxStats = [];
        $teamPeriodStats = [];
        $opponentPeriodStats = [];
        $playerStats = [];
        $opponentPlayerStats = [];
        $teamTeamStats = null;
        $opponentTeamStats = null;
        $hasSportConfig = false;
        $hasPeriodStats = false;

        if ($game->team_season && $game->team_season->team && $game->team_season->team->sport) {
            $sportId = $game->team_season->team->sport->id;
            $sportName = strtolower($game->team_season->team->sport->sport_name);
            $hasSportConfig = true;

            // Delegate sport-specific stats loading to service
            if ($sportName === 'basketball') {
                $basketballService = new \App\Service\BasketballStatsService();
                $basketballStats = $basketballService->getGameStats((int)$id);

                if ($basketballStats) {
                    $teamBoxStats = $basketballStats['teamBoxStats'] ?? [];
                    $opponentBoxStats = $basketballStats['opponentBoxStats'] ?? [];
                    $teamPeriodStats = $basketballStats['teamPeriodStats'] ?? [];
                    $opponentPeriodStats = $basketballStats['opponentPeriodStats'] ?? [];
                    $playerStats = $basketballStats['playerStats'] ?? [];
                    $opponentPlayerStats = $basketballStats['opponentPlayerStats'] ?? [];
                    $teamTeamStats = $basketballStats['teamTeamStats'] ?? null;
                    $opponentTeamStats = $basketballStats['opponentTeamStats'] ?? null;
                    $hasPeriodStats = $basketballStats['hasPeriodStats'] ?? false;
                }
            }

            // Get field labels from SportConfigService
            $fieldLabels = $this->sportConfigService->getAllFieldLabels($sportId);
            $this->set('fieldLabels', $fieldLabels);
        }

        $this->setSportAwareData($game);
        $this->set(compact(
            'game',
            'eav',
            'teamBoxStats',
            'opponentBoxStats',
            'teamPeriodStats',
            'opponentPeriodStats',
            'playerStats',
            'opponentPlayerStats',
            'teamTeamStats',
            'opponentTeamStats',
            'hasSportConfig',
            'hasPeriodStats',
        ));
    }

    /**
     * Add a new game.
     */
    public function add(): ?Response
    {
        // Require team_season_id for add
        $teamSeasonId = (int)$this->request->getQuery('team_season_id');
        if (!$teamSeasonId) {
            $this->Flash->error(__('You must add a game from within a team season.'));

            return $this->redirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
        }

        /** @var \App\Model\Entity\Game $game */
        $game = $this->Games->newEmptyEntity();
        $game->set('team_season_id', $teamSeasonId);

        // Get sportId from team season
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->fetchTable('TeamSeasons')->find()
            ->contain(['Teams' => ['Sports']])
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->firstOrFail();
        $sportId = $teamSeason->team->sport->id;
        $periods = $game->periods ?? '2';
        $overtime = $game->ot ?? '0';
        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');
        $gameEavTable->setSportConfigService($this->sportConfigService);
        $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);
        $eav = [];

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Track if new opponent is being created
            $newOpponentId = null;
            if (!empty($data['new_opponent']['opponent_name'])) {
                // Will be set in normalizeAssociatedInlineCreate
                $trackNewOpponent = true;
            }

            $this->gameService->normalizeAssociatedInlineCreate($data);

            // Check if opponent was just created
            if (isset($trackNewOpponent) && !empty($data['opponent_id'])) {
                $newOpponentId = $data['opponent_id'];
            }

            // Auto-calculate W/L based on scores
            $data = $this->gameService->calculateWinLoss($data);

            // Validate period scores if present
            $eavErrors = $this->gameService->validatePeriodScores($data);
            if (!empty($eavErrors)) {
                foreach ($eavErrors as $error) {
                    $this->Flash->error($error);
                }
                $this->setFormLists();
                $this->setSportAwareData($game);
                $eav = $data; // Keep user input for re-display
                $this->set(compact('game', 'eav', 'eavTemplate'));

                return null;
            }

            $game = $this->Games->patchEntity($game, $data);
            if ($this->Games->save($game)) {
                $this->gameService->saveGameEavFromRequest((int)$game->get('id'), $data);
                $this->Flash->success(__('The game has been saved.'));

                // Redirect to edit opponent if a new one was created
                if ($newOpponentId) {
                    return $this->redirect([
                        'prefix' => 'Admin',
                        'controller' => 'Opponents',
                        'action' => 'edit',
                        $newOpponentId,
                    ]);
                }

                return $this->redirect([
                    'prefix' => 'Admin',
                    'controller' => 'TeamSeasons',
                    'action' => 'view',
                    $teamSeasonId,
                ]);
            }
            // Validation errors are handled via Flash messages and form re-rendering
            $this->Flash->error(__('The game could not be saved. Please, try again.'));
        }

        $this->setFormLists();
        $this->setSportAwareData($game);
        $this->set(compact('game', 'eav', 'eavTemplate'));

        return null;
    }

    /**
     * Edit a game.
     *
     * @param string $id Game ID
     */
    public function edit(string $id): ?Response
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->Games->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']]])
            ->where(['Games.id' => $id])
            ->firstOrFail();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $this->gameService->normalizeAssociatedInlineCreate($data);

            // Auto-calculate W/L based on scores
            $data = $this->gameService->calculateWinLoss($data);

            // Validate period scores if present
            $eavErrors = $this->gameService->validatePeriodScores($data);
            if (!empty($eavErrors)) {
                foreach ($eavErrors as $error) {
                    $this->Flash->error($error);
                }
                $eav = $this->loadGameEavArray((int)$id);
                // Merge in the new data for re-display
                foreach ($data as $key => $value) {
                    if (strpos($key, 'period_') === 0 || strpos($key, 'overtime_') === 0) {
                        $eav[$key] = $value;
                    }
                }
                $this->setFormLists($game->place_id);
                $this->setSportAwareData($game);
                $this->set(compact('game', 'eav'));

                return null;
            }

            $game = $this->Games->patchEntity($game, $data);
            if ($this->Games->save($game)) {
                $this->gameService->saveGameEavFromRequest((int)$game->get('id'), $data);
                $this->Flash->success(__('The game has been saved.'));

                // Redirect back to team season if we have the context
                $teamSeasonId = $game->get('team_season_id');
                if ($teamSeasonId) {
                    return $this->redirect([
                        'prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId,
                    ]);
                }

                return $this->redirect(['action' => 'index']);
            }
            // Validation errors are handled via Flash messages and form re-rendering
            $this->Flash->error(__('The game could not be saved. Please, try again.'));
        }

        $eav = $this->loadGameEavArray((int)$id);
        /** @var \App\Model\Entity\Game $game */
        $this->setFormLists($game->place_id);
        $this->setSportAwareData($game);
        $this->set(compact('game', 'eav'));

        return null;
    }

    /**
     * Delete a game.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $game = $this->Games->get($id);
        if ($this->Games->delete($game)) {
            $this->Flash->success(__('The game has been deleted.'));
        } else {
            $this->Flash->error(__('The game could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for games.
     */
    public function bulk(): Response
    {
        $action = $this->request->getData('bulk_action');
        if ($action === 'delete') {
            return $this->bulkDelete();
        }

        $this->Flash->error('Invalid bulk action.');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete selected games.
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $ids = (array)$this->request->getData('game_ids');
        $ids = array_values(array_filter($ids, fn($v) => $v !== '' && $v !== null && ctype_digit((string)$v)));

        if (empty($ids)) {
            $this->Flash->error('No games selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        // Get team_season_id before deletion for redirect context
        $teamSeasonId = null;
        try {
            $firstGame = $this->Games->get($ids[0]);
            $teamSeasonId = $firstGame->get('team_season_id');
        } catch (RecordNotFoundException $e) {
            // Game doesn't exist, ignore
        }

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $entity = $this->Games->get($id);
            } catch (RecordNotFoundException $e) {
                continue;
            }
            if ($this->Games->delete($entity)) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} game(s).', $deleted));
        } else {
            $this->Flash->error(__('No games were deleted.'));
        }

        if ($teamSeasonId) {
            return $this->redirect([
                'prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId,
            ]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Build lists for select inputs and provide inline create options.
     *
     * @param int|null $placeId Optional place ID to filter sites
     */
    private function setFormLists(?int $placeId = null): void
    {
        // Get formatted lists from service
        $lists = $this->gameService->getFormLists($placeId);

        // Build team season list (still controller-specific formatting)
        $teamSeasons = $this->fetchTable('TeamSeasons')->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->orderByDesc('Seasons.start')
            ->all();
        $teamSeasonList = [];
        foreach ($teamSeasons as $ts) {
            /** @var \App\Model\Entity\TeamSeason $ts */
            $sportName = $ts->team->sport->sport_name ?? 'Unknown';
            $label = sprintf(
                '%s (%s) — %s-%s',
                ($ts->team->team_name ?? 'Team'),
                $sportName,
                ($ts->season->start ?? ''),
                ($ts->season->end ?? '')
            );
            $teamSeasonList[$ts->id] = $label;
        }

        $sports = $this->fetchTable('Sports')->find('list')->all(); // for opponent/site creation helpers

        $this->set(array_merge($lists, compact('teamSeasonList', 'sports')));
    }

    /**
     * Set sport-aware data for game forms based on the selected team's sport.
     *
     * @param \App\Model\Entity\Game $game Game entity
     * @return void
     */
    private function setSportAwareData(\App\Model\Entity\Game $game): void
    {
        $sportId = null;
        $sportName = 'Unknown';

        // Get sport information from team season
        if ($game->get('team_season_id')) {
            /** @var \App\Model\Entity\TeamSeason $teamSeason */
            $teamSeason = $this->fetchTable('TeamSeasons')->find()
                ->contain(['Teams' => ['Sports']])
                ->where(['TeamSeasons.id' => $game->get('team_season_id')])
                ->first();
            if ($teamSeason && $teamSeason->team && $teamSeason->team->sport) {
                $sportId = $teamSeason->team->sport->id;
                $sportName = $teamSeason->team->sport->sport_name;
            }
        }

        // Get sport-specific configurations
        $sportConfigs = [];
        $eavTemplate = [];
        if ($sportId) {
            /** @var \App\Model\Table\SportConfigsTable $sportConfigsTable */
            $sportConfigsTable = $this->fetchTable('SportConfigs');
            $sportConfigs = $sportConfigsTable->getFormattedConfigsForSport($sportId);

            /** @var \App\Model\Table\GameEavTable $gameEavTable */
            $gameEavTable = $this->fetchTable('GameEav');
            $gameEavTable->setSportConfigService($this->sportConfigService);
            // Pass game's periods and overtime values to generate appropriate EAV fields
            $periods = (string)($game->get('periods') ?: '2');
            $overtime = (string)($game->get('ot') ?: '0');
            $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);

            // When editing, map legacy stored keys (period_X_mur/opp) to new naming (period_X_team/opponent)
            if ($game->id) {
                $existing = $this->loadGameEavArray((int)$game->id);
                foreach ($existing as $k => $v) {
                    if (preg_match('/^period_(\d+)_mur$/', $k, $m)) {
                        $newKey = 'period_' . $m[1] . '_team';
                        if (!isset($existing[$newKey])) {
                            $existing[$newKey] = $v;
                        }
                    } elseif (preg_match('/^period_(\d+)_opp$/', $k, $m)) {
                        $newKey = 'period_' . $m[1] . '_opponent';
                        if (!isset($existing[$newKey])) {
                            $existing[$newKey] = $v;
                        }
                    }
                }
                // Expose mapped values separately so form element can access
                $this->set('legacyMappedEav', $existing);
            }
        }

        $this->set(compact('sportId', 'sportName', 'sportConfigs', 'eavTemplate'));
    }

    /**
     * Load EAV attributes into an array for a game.
     *
     * @param int $gameId Game id
     * @return array<string, mixed>
     */
    private function loadGameEavArray(int $gameId): array
    {
        return $this->gameService->loadGameEavValues($gameId);
    }
}
