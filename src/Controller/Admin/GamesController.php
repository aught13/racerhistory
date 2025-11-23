<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Admin Games Controller
 *
 * Manages games and associated values (game types, opponent, site/place) and
 * EAV attributes such as period scores and officials.
 *
 * @property \App\Model\Table\GamesTable $Games
 * @property \App\Service\GameService $Game
 * @property \App\Service\SportConfigService $SportConfig
 * @property \App\Service\BasketballStatsService $BasketballStats
 */
class GamesController extends AppController
{
    /**
     * @var \App\Service\GameService Service for game-related business logic
     */
    protected \App\Service\GameService $Game;

    /**
     * @var \App\Service\SportConfigService Service for sport configuration management
     */
    protected \App\Service\SportConfigService $SportConfig;

    /**
     * @var \App\Service\BasketballStatsService Service for basketball statistics
     */
    protected \App\Service\BasketballStatsService $BasketballStats;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadService('Game');
        $this->loadService('SportConfig');
        $this->loadService('BasketballStats');
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
            $metadata = $this->Game->getGameEavMetadata(
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

        $sites = $this->Game->getSitesByPlace($placeId);

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
        $draw = (int)$this->request->getData('draw', $this->request->getQuery('draw', 1));
        $start = (int)$this->request->getData('start', $this->request->getQuery('start', 0));
        $length = (int)$this->request->getData('length', $this->request->getQuery('length', 25));
        $searchValue = $this->request->getData('search.value', $this->request->getQuery('search.value', ''));
        $teamSeasonId = $this->request->getQuery('team_season_id');
        $searchBuilder = $this->request->getData('searchBuilder', $this->request->getQuery('searchBuilder'));

        $result = $this->Game->buildGamesDataTable([
            'start' => $start,
            'length' => $length,
            'searchValue' => $searchValue,
            'teamSeasonId' => $teamSeasonId,
            'searchBuilder' => $searchBuilder,
        ]);

        $this->set([
            'draw' => $draw,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $result['data'],
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

    /**
     * View a game with associations and EAV attributes.
     *
     * @param string $id Game ID
     */
    public function view(string $id): void
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->Game->getGameWithAssociations((int)$id);
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
                $basketballStats = $this->BasketballStats->getGameStats((int)$id);

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
            $fieldLabels = $this->SportConfig->getAllFieldLabels($sportId);
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
        $gameEavTable->setSportConfigService($this->SportConfig);
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

            $this->Game->normalizeAssociatedInlineCreate($data);

            // Check if opponent was just created
            if (isset($trackNewOpponent) && !empty($data['opponent_id'])) {
                $newOpponentId = $data['opponent_id'];
            }

            // Auto-calculate W/L based on scores
            $data = $this->Game->calculateWinLoss($data);

            // Validate period scores if present
            $eavErrors = $this->Game->validatePeriodScores($data);
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
                $this->Game->saveGameEavFromRequest((int)$game->get('id'), $data);
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
            $this->Game->normalizeAssociatedInlineCreate($data);

            // Auto-calculate W/L based on scores
            $data = $this->Game->calculateWinLoss($data);

            // Validate period scores if present
            $eavErrors = $this->Game->validatePeriodScores($data);
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
                $this->Game->saveGameEavFromRequest((int)$game->get('id'), $data);
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
        $result = $this->Game->bulkDeleteGames($ids);
        if ($result['deleted'] > 0) {
            $this->Flash->success(__('Deleted {0} game(s).', $result['deleted']));
        } else {
            $this->Flash->error(__('No games were deleted.'));
        }

        if ($result['teamSeasonId']) {
            return $this->redirect([
                'prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $result['teamSeasonId'],
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
        $lists = $this->Game->getFormLists($placeId);
        $extra = $this->Game->getTeamSeasonAndSportsLists();
        $this->set(array_merge($lists, $extra));
    }

    /**
     * Set sport-aware data for game forms based on the selected team's sport.
     *
     * @param \App\Model\Entity\Game $game Game entity
     * @return void
     */
    private function setSportAwareData(\App\Model\Entity\Game $game): void
    {
        // Resolve sport information for dynamic EAV template building
        $sportId = null;
        $sportName = 'Unknown';

        // Get sport information from team season
        if ($game->get('team_season_id')) {
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
            $sportConfigsTable = $this->fetchTable('SportConfigs');
            $sportConfigs = $sportConfigsTable->getFormattedConfigsForSport($sportId);

            $gameEavTable = $this->fetchTable('GameEav');
            $gameEavTable->setSportConfigService($this->SportConfig);
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
        return $this->Game->loadGameEavValues($gameId);
    }
}
