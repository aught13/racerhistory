<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BlogPostService;
use App\Service\GameSearchService;
use App\Service\GameService;
use App\Service\GameViewService;
use App\Service\ImageTagService;
use App\Service\OpponentService;
use App\Service\StatsService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\Routing\Router;
use DateTimeInterface;
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
        $this->gameService = new GameService();
        $this->gameViewService = new GameViewService($this->gameService);
        $this->statsService = new StatsService();
        $this->imageTagService = new ImageTagService();
        $this->blogPostService = new BlogPostService();
        $this->gameSearchService = new GameSearchService();
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
    }

    /**
     * Push search types to every view for sub-nav.
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $this->set('searchTypes', $this->searchTypes);
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
                $this->formatRankedRows($games),
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

            return $this->jsonResponse($this->formatOvertimeRows($games, 'l, F j, Y', true, true));
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

            return $this->jsonResponse($this->formatOvertimeRows($games));
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

            return $this->jsonResponse($this->formatHundredPointRows($games));
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

            return $this->jsonResponse($this->formatOpenersRows($games));
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
        $this->enrichGames($games);

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
                return $this->jsonResponse($this->formatSeriesRows($seriesData['games']));
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

        $opponentService = new OpponentService();
        $results = $opponentService->searchOpponents($q, 25);

        $opponents = [];
        foreach ($results as $opponent) {
            $opponents[] = [
                'id' => (int)$opponent->id,
                'name' => (string)($opponent->opponent_name ?? ''),
                'short' => (string)($opponent->opponent_short ?? ''),
                'label' => (string)($opponent->opponent_name ?? 'Opponent #' . $opponent->id),
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

        $rows = array_map(function (array $row): array {
            $opponentId = (int)($row['opponent_id'] ?? 0);
            $selectLink = $this->link(
                'View Series',
                ['controller' => 'Games', 'action' => 'series-history', '?' => ['opponent_id' => $opponentId]],
                ['escape' => true],
            );

            return [
                h((string)($row['opponent_name'] ?? 'Unknown')),
                h((string)($row['opponent_short'] ?? '-')),
                (int)($row['games_count'] ?? 0),
                $selectLink,
            ];
        }, $result['rows']);

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

    /**
     * Build a safe HTML link.
     *
     * @param string $text
     * @param array $url
     * @param array $options
     * @return string
     */
    protected function link(string $text, array $url, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $display = $escape ? h($text) : $text;

        return '<a href="' . h(Router::url($url)) . '">' . $display . '</a>';
    }

    /**
     * Enrich games with computed display fields.
     *
     * @param array $games
     * @return void
     */
    protected function enrichGames(array &$games): void
    {
        foreach ($games as $g) {
            try {
                $g->set('result_flag', $this->gameService->getResultFlag($g));
                $g->set('place_city', $this->gameService->getPlaceName($g));
                $g->set('place_state', $this->gameService->getPlaceState($g));
                $g->set('site_name', $this->gameService->getSiteName($g));
                $prefix = '@';
                if (!empty($g->hrn) && (int)$g->hrn === 1) {
                    $prefix = 'Vs';
                } elseif (!empty($g->hrn) && (int)$g->hrn === 3) {
                    $prefix = 'vs';
                }
                $g->set('opponent_prefix', $prefix);
                $ptsMur = (int)($g->pts_mur ?? 0);
                $ptsOpp = (int)($g->pts_opp ?? 0);
                if (!$g->has('margin')) {
                    $g->set('margin', abs($ptsMur - $ptsOpp));
                }
            } catch (Throwable $e) {
            }
        }
    }

    /**
     * HRN label.
     *
     * @param int|null $hrn
     * @return string
     */
    protected function hrnLabel(?int $hrn): string
    {
        return GameSearchService::hrnLabel($hrn);
    }

    /**
     * Format score string.
     *
     * @param object $game
     * @return string
     */
    protected function scoreStr(object $game): string
    {
        $ptsMur = $game->pts_mur ?? '-';
        $ptsOpp = $game->pts_opp ?? '-';

        return $ptsMur . '-' . $ptsOpp;
    }

    /**
     * Format OT display value.
     *
     * @param mixed $ot
     * @return string
     */
    protected function otDisplay(mixed $ot): string
    {
        if ($ot === null) {
            return '-';
        }

        $value = trim((string)$ot);
        if ($value === '' || $value === '0') {
            return '-';
        }

        return h($value);
    }

    /**
     * Format a game date with semantic ISO metadata for DataTables display.
     *
     * @param mixed $date
     * @param string $displayFormat
     * @return string
     */
    protected function formatDate(mixed $date, string $displayFormat = 'm/d/Y'): string
    {
        if ($date === null) {
            return '-';
        }
        if ($date instanceof Date || $date instanceof DateTimeInterface) {
            $iso = $date->format('Y-m-d');
            $display = $date->format($displayFormat);
        } else {
            $iso = (string)$date;
            $ts = strtotime($iso);
            $display = $ts ? date($displayFormat, $ts) : $iso;
        }

        return '<time class="rh-game-date" datetime="' . h($iso) . '">' . h($display) . '</time>';
    }

    /**
     * Format ranked game rows for DataTables.
     *
     * @param array $games
     * @return array
     */
    protected function formatRankedRows(array $games): array
    {
        $rows = [];
        foreach ($games as $g) {
            $result = $this->gameService->getResultFlag($g);
            $ptsMur = (int)($g->pts_mur ?? 0);
            $ptsOpp = (int)($g->pts_opp ?? 0);
            $seasonLabel = ($g->team_season->season->start ?? '') . '-'
                . ($g->team_season->season->end ?? '');
            $dateDisplay = $this->formatDate($g->game_date);
            $rows[] = [
                $this->link(
                    $dateDisplay,
                    ['controller' => 'Games', 'action' => 'view', $g->id],
                    ['escape' => false],
                ),
                (int)($g->mur_rk ?? 0) ?: '-',
                h($g->team_season->team->team_name ?? 'Murray State'),
                $this->link(
                    $g->opponent->opponent_name ?? '?',
                    ['controller' => 'Games', 'action' => 'series',
                     '?' => ['opponent_id' => $g->opponent->id ?? 0]],
                ),
                (int)($g->opp_rk ?? 0) ?: '-',
                $this->hrnLabel((int)($g->hrn ?? 0)),
                h($result ?? '-'),
                abs($ptsMur - $ptsOpp),
                $ptsMur,
                $ptsOpp,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $g->team_season->id ?? 0],
                ),
            ];
        }

        return $rows;
    }

    /**
     * Format overtime game rows for DataTables.
     *
     * @param array $games
     * @param string $dateFormat
     * @param bool $showConferenceTypeAbr
     * @return array
     */
    protected function formatOvertimeRows(
        array $games,
        string $dateFormat = 'm/d/Y',
        bool $showConferenceTypeAbr = false,
        bool $includeWeekdayColumn = false,
    ): array {
        $rows = [];
        foreach ($games as $g) {
            $result = $this->gameService->getResultFlag($g);
            $ptsMur = (int)($g->pts_mur ?? 0);
            $ptsOpp = (int)($g->pts_opp ?? 0);
            $isConf = (bool)($g->game_type->conf ?? false);
            $seasonLabel = ($g->team_season->season->start ?? '') . '-'
                . ($g->team_season->season->end ?? '');
            $dateDisplay = $this->formatDate($g->game_date, $dateFormat);
            $gameTypeDisplay = $g->post || ($showConferenceTypeAbr && $isConf)
                ? h((string)($g->game_type->abr ?? ($isConf ? 'Conf' : 'Post')))
                : 'Regular';
            $row = [
                $this->link(
                    $dateDisplay,
                    ['controller' => 'Games', 'action' => 'view', $g->id],
                    ['escape' => false],
                ),
                $this->link(
                    $g->opponent->opponent_abbr ?? '?',
                    ['controller' => 'Games', 'action' => 'series',
                     '?' => ['opponent_id' => $g->opponent->id ?? 0]],
                ),
                h($result ?? '-'),
                abs($ptsMur - $ptsOpp),
                $ptsMur,
                $ptsOpp,
                $this->otDisplay($g->ot ?? null),
                $this->hrnLabel((int)($g->hrn ?? 0)),
                $isConf ? 'Y' : 'N',
                $gameTypeDisplay,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $g->team_season->id ?? 0],
                ),
            ];

            if ($includeWeekdayColumn) {
                $row[] = $this->formatWeekday($g->game_date ?? null);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Format weekday label for SearchBuilder filtering.
     *
     * @param mixed $date
     * @return string
     */
    protected function formatWeekday(mixed $date): string
    {
        if ($date instanceof Date || $date instanceof DateTimeInterface) {
            return $date->format('l');
        }

        if ($date === null || $date === '') {
            return '-';
        }

        $timestamp = strtotime((string)$date);

        return $timestamp !== false ? date('l', $timestamp) : (string)$date;
    }

    /**
     * Format 100-point game rows for DataTables.
     *
     * @param array $games
     * @return array
     */
    protected function formatHundredPointRows(array $games): array
    {
        $rows = [];
        foreach ($games as $g) {
            $result = $this->gameService->getResultFlag($g);
            $ptsMur = (int)($g->pts_mur ?? 0);
            $ptsOpp = (int)($g->pts_opp ?? 0);
            $isConf = (bool)($g->game_type->conf ?? false);
            $seasonLabel = ($g->team_season->season->start ?? '') . '-'
                . ($g->team_season->season->end ?? '');
            $dateDisplay = $this->formatDate($g->game_date);
            $gameTypeDisplay = $g->post
                ? h((string)($g->game_type->abr ?? 'Post'))
                : 'Regular';
            $opponentDisplay = (string)($g->opponent->opponent_short
                ?? $g->opponent->opponent_abbr
                ?? $g->opponent->opponent_name
                ?? '?');
            $rows[] = [
                $this->link(
                    $dateDisplay,
                    ['controller' => 'Games', 'action' => 'view', $g->id],
                    ['escape' => false],
                ),
                $this->link(
                    $opponentDisplay,
                    ['controller' => 'Games', 'action' => 'series',
                     '?' => ['opponent_id' => $g->opponent->id ?? 0]],
                ),
                h($result ?? '-'),
                abs($ptsMur - $ptsOpp),
                $ptsMur,
                $ptsOpp,
                $this->otDisplay($g->ot ?? null),
                $this->hrnLabel((int)($g->hrn ?? 0)),
                $isConf ? 'Y' : 'N',
                $gameTypeDisplay,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $g->team_season->id ?? 0],
                ),
            ];
        }

        return $rows;
    }

    /**
     * Format opener game rows for DataTables.
     *
     * @param array $games
     * @return array
     */
    protected function formatOpenersRows(array $games): array
    {
        $rows = [];
        foreach ($games as $g) {
            $result = $this->gameService->getResultFlag($g);
            $ptsMur = (int)($g->pts_mur ?? 0);
            $ptsOpp = (int)($g->pts_opp ?? 0);
            $isConf = (bool)($g->game_type->conf ?? false);
            $seasonLabel = ($g->team_season->season->start ?? '') . '-'
                . ($g->team_season->season->end ?? '');
            $dateDisplay = $this->formatDate($g->game_date);
            $gameTypeDisplay = $g->post || $isConf
                ? h((string)($g->game_type->abr ?? ($isConf ? 'Conf' : 'Post')))
                : 'Regular';
            $opponentDisplay = (string)($g->opponent->opponent_short
                ?? $g->opponent->opponent_abbr
                ?? $g->opponent->opponent_name
                ?? '?');
            $rows[] = [
                $this->link(
                    $dateDisplay,
                    ['controller' => 'Games', 'action' => 'view', $g->id],
                    ['escape' => false],
                ),
                $this->link(
                    $opponentDisplay,
                    ['controller' => 'Games', 'action' => 'series',
                     '?' => ['opponent_id' => $g->opponent->id ?? 0]],
                ),
                h($result ?? '-'),
                abs($ptsMur - $ptsOpp),
                $ptsMur,
                $ptsOpp,
                $this->otDisplay($g->ot ?? null),
                $this->hrnLabel((int)($g->hrn ?? 0)),
                $isConf ? 'Y' : 'N',
                $gameTypeDisplay,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $g->team_season->id ?? 0],
                ),
            ];
        }

        return $rows;
    }

    /**
     * Format series history game rows for DataTables.
     *
     * @param array $games
     * @return array
     */
    protected function formatSeriesRows(array $games): array
    {
        $rows = [];
        foreach ($games as $g) {
            $result = $g->result_flag ?? $this->gameService->getResultFlag($g);
            $ptsMur = (int)($g->pts_mur ?? 0);
            $ptsOpp = (int)($g->pts_opp ?? 0);
            $rows[] = [
                $this->formatDate($g->game_date),
                h($result ?? '-'),
                $this->scoreStr($g),
                $this->hrnLabel((int)($g->hrn ?? 0)),
                abs($ptsMur - $ptsOpp),
                $ptsMur,
                $ptsOpp,
                h(($g->team_season->season->start ?? '') . '-' . ($g->team_season->season->end ?? '')),
                $this->link('View', ['controller' => 'Games', 'action' => 'view', $g->id]),
            ];
        }

        return $rows;
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

        $this->enrichGameDisplay($game);

        $statsElement = $this->statsService->getGameStatsElement($id);
        $images = $this->imageTagService->getImagesByAllTags(["game-{$id}"], 12);
        $blogPosts = $this->blogPostService->getPublishedByTag("game-{$id}", 12);

        return $viewData + compact('game', 'statsElement', 'images', 'blogPosts');
    }

    /**
     * Runs the enrich game display routine.
     *
     * @param object $game
     * @return void
     */
    private function enrichGameDisplay(object $game): void
    {
        try {
            $game->set('result_flag', $this->gameService->getResultFlag($game));
            $game->set('place_city', $this->gameService->getPlaceName($game));
            $game->set('place_state', $this->gameService->getPlaceState($game));
            $game->set('site_name', $this->gameService->getSiteName($game));
            $prefix = '@';
            if (!empty($game->hrn) && (int)$game->hrn === 1) {
                $prefix = 'Vs';
            } elseif (!empty($game->hrn) && (int)$game->hrn === 3) {
                $prefix = 'vs';
            }
            $game->set('opponent_prefix', $prefix);
        } catch (Throwable $e) {
            // ignore enrichment errors
        }
    }
}
