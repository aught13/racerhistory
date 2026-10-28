<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BlogPostService;
use App\Service\GameListViewService;
use App\Service\GameSearchService;
use App\Service\GameService;
use App\Service\GameViewService;
use App\Service\ImageTagService;
use App\Service\OpponentService;
use App\Service\StatsService;
use Cake\Controller\ComponentRegistry;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Throwable;

/**
 * Public Games Controller
 *
 * Displays games, box scores, predefined game searches,
 * specialty lists (streaks, margins), and series history.
 *
 * Inherits from AppController which provides common functionality and layout for the site.
 * The controller uses several services to fetch and format data for the views, and it provides JSON endpoints for DataTables integration on the frontend.
 *
 * Actions:
 * - index(): Games landing page with search type cards.
 * - ranked(): Ranked games (team or opponent ranked).
 * - all(): All games.
 * - overtime(): Overtime games.
 * - hundredPoint(): 100 point games.
 * - openers(): Season openers.
 * - streaks(): Winning/losing streaks.
 * - margins(): Largest margin wins/losses.
 * - series(): Series history against a specific opponent.
 * - searchOpponents(): AJAX endpoint for opponent search autocomplete.
 * - seriesOpponents(): AJAX endpoint for opponents list in series DataTable.
 * - view($id): View a single game with box score if available.
 *
 * Security:
 * - All actions skip authorization checks to allow public access to game information.
 * - Input parameters are validated and sanitized to prevent injection attacks.
 * - JSON endpoints ensure that only expected parameters are processed and that output is properly escaped.
 * - The controller is designed to be resilient against missing or malformed data, with appropriate error handling and fallbacks.
 *
 * Dependencies:
 * - GameService: Provides methods for retrieving game data and computing display fields like result flags and
 * place names.
 * - GameViewService: Assembles comprehensive view data for the game detail page, including
 * game information, stats, and related content.
 * - StatsService: Provides methods for retrieving and formatting game stats for display in the box score
 * frame.
 * - ImageTagService: Fetches images associated with specific game tags for display on the game
 * detail page.
 * - BlogPostService: Fetches blog posts associated with specific game tags for display on the
 * game detail page.
 * - GameSearchService: Provides methods for performing predefined game searches and formatting results for DataTables.
 *
 * Components:
 * - AuthorizationComponent: Used to skip authorization checks for all actions in this controller, as the
 * game information is intended to be publicly accessible. This also allows for future access control if needed (e.g. for admin-only stats exports).
 * - RequestHandlerComponent: Can be used to automatically detect AJAX requests and set response types, although in this implementation we manually check for JSON requests in each action.
 * - The controller also includes helper methods for formatting data for display and for building JSON responses in a consistent format for DataTables.
 *
 * This controller is public and does not require authentication, but it uses the Authorization component
 * to allow for future access control if needed (e.g. for admin-only stats exports).
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \App\Model\Table\GamesTable $Games
 */
class GamesController extends AppController
{
    private GameService $gameService;
    private GameViewService $gameViewService;
    private StatsService $statsService;
    private ImageTagService $imageTagService;
    private BlogPostService $blogPostService;
    private GameSearchService $gameSearchService;
    private GameListViewService $gameListViewService;
    private OpponentService $opponentService;

    /**
     * @var array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    private array $publicScope = ['valid' => true, 'error' => null, 'sportKey' => null, 'teamId' => null];

    /**
     * Create the controller with its game and view services.
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param \App\Service\GameService $gameService The game service.
     * @param \App\Service\GameViewService $gameViewService The game view service.
     * @param \App\Service\StatsService $statsService The statistics service.
     * @param \App\Service\ImageTagService $imageTagService The image tag service.
     * @param \App\Service\BlogPostService $blogPostService The blog post service.
     * @param \App\Service\GameSearchService $gameSearchService The game search service.
     * @param \App\Service\GameListViewService $gameListViewService The game list view service.
     * @param \App\Service\OpponentService $opponentService The opponent service.
     * @param \Cake\Controller\ComponentRegistry|null $components The component registry.
     */
    public function __construct(
        ServerRequest $request,
        GameService $gameService,
        GameViewService $gameViewService,
        StatsService $statsService,
        ImageTagService $imageTagService,
        BlogPostService $blogPostService,
        GameSearchService $gameSearchService,
        GameListViewService $gameListViewService,
        OpponentService $opponentService,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($request, null, null, $components);
        $this->gameService = $gameService;
        $this->gameViewService = $gameViewService;
        $this->statsService = $statsService;
        $this->imageTagService = $imageTagService;
        $this->blogPostService = $blogPostService;
        $this->gameSearchService = $gameSearchService;
        $this->gameListViewService = $gameListViewService;
        $this->opponentService = $opponentService;
    }

    /**
     * Predefined search types for sub-nav.
     *
     * @var array<string, string>
     */
    protected array $searchTypes = [
        'all' => 'All Games',
        'ranked' => 'Ranked',
        'overtime' => 'Overtime',
        'hundred-point' => '100 Point',
        'openers' => 'Openers',
        'streaks' => 'Streaks',
        'margins' => 'Margins',
        'series' => 'Series History',
    ];

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
    }

    /**
     * Skip authorization for public actions.
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();

        $this->publicScope = $this->PublicScope->resolve($this->request->getQueryParams());
        if (!$this->publicScope['valid']) {
            throw new BadRequestException('Invalid sport or team selection.');
        }

        $this->gameSearchService->setPublicScope(
            $this->publicScope['sportKey'],
            $this->publicScope['teamId'],
        );

        $action = (string)$this->request->getParam('action');
        if ($action === 'hundredPoint' && $this->publicScope['sportKey'] !== 'basketball') {
            $this->setResponse($this->redirect([
                'controller' => 'Games',
                'action' => 'index',
                '?' => $this->getPublicScopeQuery(),
            ]));

            return;
        }

        $teamScopedActions = [
            'ranked',
            'overtime',
            'hundredPoint',
            'openers',
            'streaks',
            'margins',
            'series',
            'seriesOpponents',
        ];
        if (in_array($action, $teamScopedActions, true) && $this->publicScope['teamId'] === null) {
            $this->setResponse($this->redirect([
                'controller' => 'Games',
                'action' => 'index',
                '?' => $this->getPublicScopeQuery(),
            ]));

            return;
        }
    }

    /**
     * Push search types to every view for sub-nav.
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $searchTypes = $this->searchTypes;
        if ($this->publicScope['teamId'] === null) {
            $searchTypes = ['all' => $this->searchTypes['all']];
        } elseif ($this->publicScope['sportKey'] !== 'basketball') {
            unset($searchTypes['hundred-point']);
        }
        $this->set([
            'searchTypes' => $searchTypes,
            'selectedTeamId' => $this->publicScope['teamId'],
        ]);
    }

    /**
     * @return array{sport:string,team:string}
     */
    private function getPublicScopeQuery(): array
    {
        return [
            'sport' => $this->publicScope['sportKey'] ?? 'all',
            'team' => $this->publicScope['teamId'] === null ? 'all' : (string)$this->publicScope['teamId'],
        ];
    }

    /**
     * Resolve a route parameter first, then a query parameter, then a default.
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    protected function resolveMenuParam(string $name, string $default): string
    {
        $value = $this->getRequest()->getParam($name);

        if ($value === null || $value === '') {
            $value = $this->getRequest()->getQuery($name, $default);
        }

        return (string)$value;
    }

    /**
     * Games landing page with search type cards.
     */
    public function index(): void
    {
        $this->set('currentSearch', '');
    }

    /**
     * Ranked games (team or opponent ranked).
     *
     * @return \Cake\Http\Response|null
     */
    public function ranked(): ?Response
    {
        $filter = $this->resolveMenuParam('filter', 'all');
        $allowed = ['all', 'team', 'opponent'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        if ($this->isJsonRequest()) {
            $games = $this->gameSearchService->rankedGames($filter);
            $wins = 0;
            $losses = 0;
            foreach ($games as $g) {
                $result = $this->gameService->getResultFlag($g);
                if ($result === 'W') {
                    $wins++;
                } elseif ($result === 'L') {
                    $losses++;
                }
            }

            return $this->jsonResponse(
                $this->gameListViewService->rankedRows($games, $this->getPublicScopeQuery()),
                ['record' => "$wins-$losses"],
            );
        }

        $this->set('currentSearch', 'ranked');
        $this->set('rankedFilter', $filter);
        $this->setGamesDateBounds('ranked', ['filter' => $filter]);

        return null;
    }

    /**
     * All games.
     *
     * @return \Cake\Http\Response|null
     */
    public function all(): ?Response
    {
        if ($this->isJsonRequest()) {
            $games = $this->gameSearchService->allGames();

            return $this->jsonResponse($this->gameListViewService->overtimeRows(
                $games,
                $this->getPublicScopeQuery(),
                'l, F j, Y',
                true,
                true,
                true,
            ));
        }

        $this->set('currentSearch', 'all');
        $this->setGamesDateBounds('all');

        return null;
    }

    /**
     * Overtime games.
     *
     * @return \Cake\Http\Response|null
     */
    public function overtime(): ?Response
    {
        if ($this->isJsonRequest()) {
            $games = $this->gameSearchService->overtimeGames();

            return $this->jsonResponse($this->gameListViewService->overtimeRows(
                $games,
                $this->getPublicScopeQuery(),
            ));
        }

        $this->set('currentSearch', 'overtime');
        $this->setGamesDateBounds('overtime');

        return null;
    }

    /**
     * 100 point games.
     *
     * @return \Cake\Http\Response|null
     */
    public function hundredPoint(): ?Response
    {
        $filter = $this->resolveMenuParam('filter', 'all');
        $allowed = ['all', 'team', 'opponent'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        if ($this->isJsonRequest()) {
            $games = $this->gameSearchService->hundredPointGames($filter);

            return $this->jsonResponse($this->gameListViewService->hundredPointRows(
                $games,
                $this->getPublicScopeQuery(),
            ));
        }

        $this->set('currentSearch', 'hundred-point');
        $this->set('hundredPointFilter', $filter);
        $this->setGamesDateBounds('hundred-point', ['filter' => $filter]);

        return null;
    }

    /**
     * Season openers.
     *
     * @return \Cake\Http\Response|null
     */
    public function openers(): ?Response
    {
        $type = $this->resolveMenuParam('type', 'season');
        $typeAliases = [
            'conference' => 'conf',
            'conference-home' => 'conf_home',
        ];
        $type = $typeAliases[$type] ?? $type;
        $allowed = ['season', 'home', 'conf', 'conf_home'];
        if (!in_array($type, $allowed, true)) {
            $type = 'season';
        }

        if ($this->isJsonRequest()) {
            $games = $this->gameSearchService->openers($type);

            return $this->jsonResponse($this->gameListViewService->openerRows(
                $games,
                $this->getPublicScopeQuery(),
            ));
        }

        $this->set('currentSearch', 'openers');
        $this->set('openerType', $type);
        $this->setGamesDateBounds('openers', ['type' => $type]);

        return null;
    }

    /**
     * Streaks page.
     */
    public function streaks(): void
    {
        $resultType = (string)$this->getRequest()->getQuery('result', 'W');
        $filter = (string)$this->getRequest()->getQuery('filter', 'overall');
        $allowedResults = ['W', 'L'];
        $allowedFilters = ['overall', 'home', 'road', 'conf', 'conf_home', 'conf_road'];

        if (!in_array($resultType, $allowedResults, true)) {
            $resultType = 'W';
        }
        if (!in_array($filter, $allowedFilters, true)) {
            $filter = 'overall';
        }

        $streaks = $this->gameSearchService->streaks($resultType, $filter);

        $this->set('currentSearch', 'streaks');
        $this->set(compact('streaks', 'resultType', 'filter'));
    }

    /**
     * Margin records page.
     */
    public function margins(): void
    {
        $type = (string)$this->getRequest()->getQuery('type', 'win');
        $filter = (string)$this->getRequest()->getQuery('filter', 'overall');
        $allowedTypes = ['win', 'loss'];
        $allowedFilters = ['overall', 'home', 'road', 'neutral', 'conf', 'conf_home', 'conf_road'];

        if (!in_array($type, $allowedTypes, true)) {
            $type = 'win';
        }
        if (!in_array($filter, $allowedFilters, true)) {
            $filter = 'overall';
        }

        $games = $this->gameSearchService->margins($type, $filter);
        $games = $this->gameListViewService->enrichGames($games);

        $this->set('currentSearch', 'margins');
        $this->set(compact('games', 'type', 'filter'));
    }

    /**
     * Series history page.
     *
     * @return \Cake\Http\Response|null
     */
    public function series(): ?Response
    {
        $opponentId = $this->getRequest()->getQuery('opponent_id');
        $opponents = $this->gameSearchService->getOpponentsList();

        if ($opponentId !== null && $opponentId !== '') {
            $opponentId = (int)$opponentId;
            $seriesData = $this->gameSearchService->seriesHistory($opponentId);

            if ($this->isJsonRequest()) {
                return $this->jsonResponse($this->gameListViewService->seriesRows(
                    $seriesData['games'],
                    $this->getPublicScopeQuery(),
                ));
            }

            $this->set('record', $seriesData['record']);
            $this->set('seriesGames', $seriesData['games']);
            $this->set('selectedOpponent', $opponentId);
            $this->set('opponentName', $opponents[$opponentId] ?? 'Unknown');
            $this->setGamesDateBounds('series', ['opponentId' => $opponentId]);
        }

        $this->set('currentSearch', 'series');
        $this->set('opponents', $opponents);

        return null;
    }

    /**
     * Search opponents for autocomplete (AJAX endpoint).
     *
     * @return \Cake\Http\Response
     */
    public function searchOpponents(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q'));

        if ($q === '') {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true, 'opponents' => []]));
        }

        $results = $this->opponentService->searchOpponents($q, 25);

        $opponents = [];
        foreach ($results as $opponent) {
            $opponents[] = [
                'id' => (int)$opponent->id,
                'name' => $opponent->getDisplayName(),
                'short' => (string)($opponent->opponent_short ?? ''),
                'label' => $opponent->getDisplayName(),
                'opponent_name' => $opponent->opponent_name,
            ];
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'opponents' => $opponents]));
    }

    /**
     * Return opponents list for the series DataTable.
     *
     * @return \Cake\Http\Response
     */
    public function seriesOpponents(): Response
    {
        $this->request->allowMethod(['get']);

        $draw = (int)$this->request->getQuery('draw', 0);
        $start = max(0, (int)$this->request->getQuery('start', 0));
        $length = (int)$this->request->getQuery('length', 50);
        if ($length < 1 || $length > 250) {
            $length = 50;
        }

        $search = '';
        $searchQuery = $this->request->getQuery('search');
        if (is_array($searchQuery)) {
            $search = trim((string)($searchQuery['value'] ?? ''));
        }

        $result = $this->gameSearchService->searchSeriesOpponents($search, $start, $length);

        $rows = $this->gameListViewService->seriesOpponentRows(
            $result['rows'],
            $this->getPublicScopeQuery(),
        );

        return $this->jsonResponse($rows, [
            'draw' => $draw,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
        ]);
    }

    // ─── Helper methods ───────────────────────────────────────────────

    /**
     * Check if the current request expects JSON.
     *
     * @return bool
     */
    protected function isJsonRequest(): bool
    {
        return $this->getRequest()->is('ajax')
            || $this->getRequest()->getQuery('format') === 'json';
    }

    /**
     * Return a DataTables-compatible JSON response.
     *
     * @param array $rows
     * @param array $meta
     * @return \Cake\Http\Response
     */
    protected function jsonResponse(array $rows, array $meta = []): Response
    {
        $response = ['data' => $rows];
        if (!empty($meta)) {
            $response = array_merge($response, $meta);
        }

        return $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode($response));
    }

    /**
     * Push SearchBuilder date bounds for public games tables into the view.
     *
     * @param string $searchType
     * @param array<string, mixed> $options
     * @return void
     */
    protected function setGamesDateBounds(string $searchType, array $options = []): void
    {
        $this->set(
            'gamesDateBounds',
            $this->gameSearchService->publicGameDateBounds($searchType, $options),
        );
    }

    // ─── Existing game view/stats actions ─────────────────────────────

    /**
     * View a single game with box score if available.
     *
     * @param int $id
     */
    public function view(int $id): void
    {
        $viewData = $this->buildGameViewData($id);

        $this->set($viewData);
    }

    /**
     * Render stats frame for Turbo requests.
     *
     * @param int $id
     * @return void
     */
    public function stats(int $id): void
    {
        $viewData = $this->buildGameViewData($id);

        $this->viewBuilder()
            ->setLayout(null)
            ->setTemplate('stats');

        $this->set($viewData);
    }

    /**
     * Runs the build game view data routine.
     *
     * @param int $id
     * @return array<string,mixed>
     */
    private function buildGameViewData(int $id): array
    {
        try {
            $viewData = $this->gameViewService->getViewData($id);
        } catch (Throwable $e) {
            throw new NotFoundException('Game not found');
        }

        $game = $viewData['game'] ?? null;
        if (!$game) {
            throw new NotFoundException('Game not found');
        }

        $teamId = (int)($game->team_season->team_id ?? 0);
        if (
            ($this->publicScope['teamId'] !== null && $this->publicScope['teamId'] !== $teamId)
            || ($this->publicScope['sportKey'] !== null && !$this->PublicScope->resolve([
                'sport' => $this->publicScope['sportKey'],
                'team' => (string)$teamId,
            ])['valid'])
        ) {
            throw new NotFoundException('Game not found');
        }

        $this->gameListViewService->enrichGameDisplay($game);

        $statsElement = $this->statsService->getGameStatsElement($id);
        $images = $this->imageTagService->getImagesByAllTags(["game-{$id}"], 12);
        $blogPosts = $this->blogPostService->getPublishedByTag("game-{$id}", 12);

        return $viewData + compact('game', 'statsElement', 'images', 'blogPosts');
    }
}
