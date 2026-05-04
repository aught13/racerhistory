<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\GameEavMetaService;
use App\Service\GameEavUiService;
use App\Service\GamesAdminService;
use App\Service\GameService;
use App\Service\GameUpsertService;
use App\Service\GameViewService;
use App\Service\SportConfigService;
use App\Service\StatsService;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * Admin Games Controller
 *
 * Thin HTTP orchestrator for admin game-management screens.
 *
 * Core workflows are delegated to dedicated services:
 * - GamesAdminService: index context, DataTables payloads, form lists,
 *   sites-by-place lookup, delete and bulk-delete orchestration.
 * - GameUpsertService: add/edit/add-results workflows.
 * - GameViewService: full view-page data assembly.
 * - GameEavMetaService/GameEavUiService: sport-aware EAV metadata and UI vars.
 *
 * The controller keeps HTTP-only concerns: request method checks, flashes,
 * redirects, and response serialization.
 *
 * @property \App\Service\GamesAdminService $gamesAdminService
 * @property \App\Service\GameService $Game
 * @property \App\Service\SportConfigService $SportConfig
 * @property \App\Service\StatsService $Stats
 * @property \App\Service\GameEavMetaService $gameEavMeta
 * @property \App\Service\GameEavUiService $gameEavUi
 * @property \App\Service\GameViewService $gameView
 * @property \App\Service\GameUpsertService $gameUpsert
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\GamesTable $Games
 */
class GamesController extends AppController
{
    /**
     * @var \App\Service\GamesAdminService
     */
    private GamesAdminService $gamesAdminService;

    private GameEavUiService $gameEavUi;
    private GameEavMetaService $gameEavMeta;
    private GameViewService $gameView;
    private GameUpsertService $gameUpsert;

    /**
     * @var \App\Service\GameService Service for game-related business logic
     */
    protected GameService $Game;

    /**
     * @var \App\Service\SportConfigService Service for sport configuration management
     */
    protected SportConfigService $SportConfig;

    /**
     * @var \App\Service\StatsService Service for sport-specific statistics
     */
    protected StatsService $Stats;

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

        $this->gamesAdminService = new GamesAdminService($this->Game, $this->SportConfig);

        $this->gameEavUi = new GameEavUiService();
        $this->gameEavMeta = new GameEavMetaService($this->Game, $this->gameEavUi);
        $this->gameView = new GameViewService($this->Game, $this->SportConfig, $this->Stats, $this->gameEavUi);
        $this->gameUpsert = new GameUpsertService(null, $this->Game, $this->SportConfig, $this->gameEavUi);
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

        $result = $this->gameEavMeta->getMetadataResult($gameId ?: null, $teamSeasonId ?: null);
        $payload = $result['payload'];

        // If HTML fragment requested, render the server-side element and return HTML
        $format = $this->request->getQuery('format');
        if ($format === 'html' && !empty($payload['success']) && !empty($result['metadata'])) {
            $vars = $this->gameEavMeta->buildSportSpecificFieldsElementVars($result['metadata']);

            $html = $this->viewBuilder()
                ->setClassName('App\View\AppView')
                ->build()
                ->element('Admin/Games/sport_specific_fields', $vars);

            return $this->response->withType('text/html')->withStringBody($html);
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

        $sites = $this->gamesAdminService->getSitesByPlace($placeId);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['sites' => $sites]));
    }

    /**
     * List games with associations.
     */
    public function index(): void
    {
        $teamSeasonIdRaw = $this->request->getQuery('team_season_id');
        $teamSeasonId = $teamSeasonIdRaw !== null ? (int)$teamSeasonIdRaw : null;
        $this->set($this->gamesAdminService->getIndexContext($teamSeasonId));
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

        $result = $this->gamesAdminService->buildDataTablePayload([
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

        if ($this->request->is('post')) {
            $result = $this->gameUpsert->processAdd($teamSeasonId, $this->request->getData());

            foreach (($result['flashErrors'] ?? []) as $error) {
                $this->Flash->error(__((string)$error));
            }
            if (!empty($result['flashSuccess'])) {
                $this->Flash->success(__((string)$result['flashSuccess']));
            }
            if (!empty($result['redirect'])) {
                return $this->redirect($result['redirect']);
            }

            $this->setFormLists();
            $this->set($result['viewData'] ?? []);

            return null;
        }

        $this->setFormLists();
        $this->set($this->gameUpsert->getAddViewData($teamSeasonId));

        return null;
    }

    /**
     * Add results to an existing game (scores, EAV fields).
     *
     * @param string $id Game ID
     */
    public function addResults(string $id): ?Response
    {
        $viewData = $this->gameUpsert->getEditViewData((int)$id);
        $game = $viewData['game'];

        $sportId = (int)($viewData['sportId'] ?? 0);
        $sportHasStats = $this->gamesAdminService->hasStats($sportId);
        $viewData['sportHasStats'] = $sportHasStats;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->gameUpsert->processEdit((int)$id, $this->request->getData());

            foreach (($result['flashErrors'] ?? []) as $error) {
                $this->Flash->error(__((string)$error));
            }
            if (!empty($result['flashSuccess'])) {
                $this->Flash->success(__((string)$result['flashSuccess']));
            }
            if (!empty($result['redirect'])) {
                // After results save with stats sport, offer box score
                if ($sportHasStats) {
                    $this->Flash->success(__('Would you like to enter box scores? {0}', sprintf(
                        '<a href="%s" class="alert-link">Enter Box Scores</a>',
                        Router::url([
                            'prefix' => 'Admin',
                            'controller' => 'StatBasketGameBox',
                            'action' => 'gameBox',
                            $game->id,
                        ]),
                    )), ['escape' => false]);
                }

                return $this->redirect($result['redirect']);
            }

            $viewData = array_merge($viewData, $result['viewData'] ?? []);
            $viewData['sportHasStats'] = $sportHasStats;
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Edit a game.
     *
     * @param string $id Game ID
     */
    public function edit(string $id): ?Response
    {
        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->gameUpsert->processEdit((int)$id, $this->request->getData());

            foreach (($result['flashErrors'] ?? []) as $error) {
                $this->Flash->error(__((string)$error));
            }
            if (!empty($result['flashSuccess'])) {
                $this->Flash->success(__((string)$result['flashSuccess']));
            }
            if (!empty($result['redirect'])) {
                if ($this->request->getData('save_action') === 'box_score') {
                    return $this->redirect([
                        'prefix' => 'Admin',
                        'controller' => 'StatBasketGameBox',
                        'action' => 'gameBox',
                        $id,
                    ]);
                }

                return $this->redirect($result['redirect']);
            }

            $this->setFormLists($result['placeId'] ?? null);
            $viewData = $result['viewData'] ?? [];
            $viewData['sportHasStats'] = $this->determineSportHasStats($viewData['sportId'] ?? 0);
            $this->set($viewData);

            return null;
        }

        $viewData = $this->gameUpsert->getEditViewData((int)$id);
        $viewData['sportHasStats'] = $this->determineSportHasStats($viewData['sportId'] ?? 0);
        $this->setFormLists($viewData['game']->place_id ?? null);
        $this->set($viewData);

        return null;
    }

    /**
     * Determine if a sport has stat tables configured.
     *
     * @param int $sportId Sport ID
     * @return bool
     */
    private function determineSportHasStats(int $sportId): bool
    {
        return $this->gamesAdminService->hasStats($sportId);
    }

    /**
     * Delete a game.
     *
     * @param string $id
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if ($this->gamesAdminService->delete((int)$id)) {
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
        $result = $this->gamesAdminService->bulkDelete($ids);
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
        $this->set($this->gamesAdminService->getFormLists($placeId));
    }

    // Sport-aware UI vars and legacy EAV mapping are handled via GameService + GameEavUiService.
}
