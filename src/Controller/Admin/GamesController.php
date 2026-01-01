<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\GameEavUiService;
use App\Service\GameViewService;
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
 * @property \App\Service\StatsService $Stats
 */
class GamesController extends AppController
{
    private GameEavUiService $gameEavUi;
    private GameViewService $gameView;

    /**
     * @var \App\Service\GameService Service for game-related business logic
     */
    protected \App\Service\GameService $Game;

    /**
     * @var \App\Service\SportConfigService Service for sport configuration management
     */
    protected \App\Service\SportConfigService $SportConfig;

    /**
     * @var \App\Service\StatsService Service for sport-specific statistics
     */
    protected \App\Service\StatsService $Stats;

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
        $this->loadService('Stats');

        $this->gameEavUi = new GameEavUiService();
        $this->gameView = new GameViewService($this->Game, $this->SportConfig, $this->Stats, $this->gameEavUi);
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
                $legacyMappedEav = $this->gameEavUi->mapLegacyKeys($metadata['values']);
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
        $this->set($this->gameView->getViewData((int)$id));
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

        $metadata = $this->Game->getGameEavMetadata(null, $teamSeasonId);
        $sportId = (int)$metadata['sportId'];
        $sportName = (string)($metadata['sportName'] ?? '');
        $sportConfigs = $metadata['configs'];
        $eavTemplate = $metadata['eavTemplate'];
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

            // Validate period scores if present (sport-agnostic via SportConfigService)
            $eavErrors = $this->SportConfig->validatePeriodScores($sportId, $data);
            if (!empty($eavErrors)) {
                foreach ($eavErrors as $error) {
                    $this->Flash->error($error);
                }
                $this->setFormLists();
                $eav = $data; // Keep user input for re-display
                $legacyMappedEav = $this->gameEavUi->mapLegacyKeys($eav);
                $this->set(compact('sportId', 'sportName', 'sportConfigs', 'eavTemplate', 'legacyMappedEav'));
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
        $legacyMappedEav = $this->gameEavUi->mapLegacyKeys($eav);
        $this->set(compact('sportId', 'sportName', 'sportConfigs', 'eavTemplate', 'legacyMappedEav'));
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

        $metadata = $this->Game->getGameEavMetadata((int)$id, null);
        $sportId = (int)$metadata['sportId'];
        $sportName = (string)($metadata['sportName'] ?? '');
        $sportConfigs = $metadata['configs'];
        $eavTemplate = $metadata['eavTemplate'];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $this->Game->normalizeAssociatedInlineCreate($data);

            // Auto-calculate W/L based on scores
            $data = $this->Game->calculateWinLoss($data);

            // Get sportId from game's team season for validation
            $sportId = $game->team_season->team->sport->id ?? null;

            // Validate period scores if present (sport-agnostic via SportConfigService)
            $eavErrors = $sportId ? $this->SportConfig->validatePeriodScores($sportId, $data) : [];
            if (!empty($eavErrors)) {
                foreach ($eavErrors as $error) {
                    $this->Flash->error($error);
                }
                $eav = $this->gameEavUi->mergePostedPeriodAndOvertimeFields($metadata['values'], $data);
                $legacyMappedEav = $this->gameEavUi->mapLegacyKeys($eav);
                $this->setFormLists($game->place_id);
                $this->set(compact('sportId', 'sportName', 'sportConfigs', 'eavTemplate', 'legacyMappedEav'));
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

        $eav = $metadata['values'];
        $legacyMappedEav = $this->gameEavUi->mapLegacyKeys($eav);
        /** @var \App\Model\Entity\Game $game */
        $this->setFormLists($game->place_id);
        $this->set(compact('sportId', 'sportName', 'sportConfigs', 'eavTemplate', 'legacyMappedEav'));
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

    // Sport-aware UI vars and legacy EAV mapping are handled via GameService + GameEavUiService.
}
