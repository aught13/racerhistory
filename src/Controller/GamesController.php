<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BlogPostService;
use App\Service\GameSearchService;
use App\Service\GameService;
use App\Service\GameViewService;
use App\Service\ImageTagService;
use App\Service\StatsService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * Public Games Controller
 *
 * Displays games, box scores, predefined game searches,
 * specialty lists (streaks, margins), and series history.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
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
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
    }

    /**
     * Push search types to every view for sub-nav.
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $this->set('searchTypes', $this->searchTypes);
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
        $filter = (string)$this->getRequest()->getQuery('filter', 'all');
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
                ['record' => "$wins-$losses"]
            );
        }

        $this->set('currentSearch', 'ranked');
        $this->set('rankedFilter', $filter);

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

        return null;
    }

    /**
     * 100 point games.
     *
     * @return \Cake\Http\Response|null
     */
    public function hundredPoint(): ?Response
    {
        if ($this->isJsonRequest()) {
            $games = $this->gameSearchService->hundredPointGames();

            return $this->jsonResponse($this->formatHundredPointRows($games));
        }

        $this->set('currentSearch', 'hundred-point');

        return null;
    }

    /**
     * Season openers.
     *
     * @return \Cake\Http\Response|null
     */
    public function openers(): ?Response
    {
        $type = (string)$this->getRequest()->getQuery('type', 'season');
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
        }

        $this->set('currentSearch', 'series');
        $this->set('opponents', $opponents);

        return null;
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
     * @param array $meta Optional metadata to include in response
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
     * Build a safe HTML link.
     *
     * @param string $text
     * @param array $url CakePHP URL array
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
                $g->set('place_name', $this->gameService->getPlaceName($g));
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
            } catch (\Throwable $e) {
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
     * Format a game date as MM/DD/YYYY with a hidden ISO prefix for DataTables sorting.
     *
     * @param mixed $date
     * @return string
     */
    protected function formatDate(mixed $date): string
    {
        if ($date === null) {
            return '-';
        }
        if ($date instanceof \Cake\I18n\Date || $date instanceof \DateTimeInterface) {
            $iso = $date->format('Y-m-d');
            $display = $date->format('m/d/Y');
        } else {
            $iso = (string)$date;
            $ts = strtotime($iso);
            $display = $ts ? date('m/d/Y', $ts) : $iso;
        }

        return '<span class="d-none">' . h($iso) . '</span>' . h($display);
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
                    ['escape' => false]
                ),
                (int)($g->mur_rk ?? 0) ?: '-',
                h($g->team_season->team->team_name ?? 'Murray State'),
                $this->link(
                    $g->opponent->opponent_name ?? '?',
                    ['controller' => 'Games', 'action' => 'series',
                     '?' => ['opponent_id' => $g->opponent->id ?? 0]]
                ),
                (int)($g->opp_rk ?? 0) ?: '-',
                $this->hrnLabel((int)($g->hrn ?? 0)),
                h($result ?? '-'),
                abs($ptsMur - $ptsOpp),
                $ptsMur,
                $ptsOpp,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $g->team_season->id ?? 0]
                ),
            ];
        }

        return $rows;
    }

    /**
     * Format overtime game rows for DataTables.
     *
     * @param array $games
     * @return array
     */
    protected function formatOvertimeRows(array $games): array
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
            $rows[] = [
                $this->link(
                    $dateDisplay,
                    ['controller' => 'Games', 'action' => 'view', $g->id],
                    ['escape' => false]
                ),
                $this->link(
                    $g->opponent->opponent_abbr ?? '?',
                    ['controller' => 'Games', 'action' => 'series',
                     '?' => ['opponent_id' => $g->opponent->id ?? 0]]
                ),
                h($result ?? '-'),
                abs($ptsMur - $ptsOpp),
                $ptsMur,
                $ptsOpp,
                h((string)($g->ot ?? '1')),
                $this->hrnLabel((int)($g->hrn ?? 0)),
                $isConf ? 'Y' : 'N',
                $gameTypeDisplay,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $g->team_season->id ?? 0]
                ),
            ];
        }

        return $rows;
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
            $rows[] = [
                $this->formatDate($g->game_date),
                $this->link(
                    $g->opponent->opponent_name ?? '?',
                    ['controller' => 'Games', 'action' => 'view', $g->id]
                ),
                h($result ?? '-'),
                $this->scoreStr($g),
                $this->hrnLabel((int)($g->hrn ?? 0)),
                abs($ptsMur - $ptsOpp),
                h((string)($g->ot ?? '')),
                $isConf ? 'Conf' : 'Non-Conf',
                $ptsMur,
                $ptsOpp,
                $g->post ? 'Post' : 'Regular',
                h(($g->team_season->season->start ?? '') . '-' . ($g->team_season->season->end ?? '')),
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
            $rows[] = [
                $this->formatDate($g->game_date),
                $this->link(
                    $g->opponent->opponent_name ?? '?',
                    ['controller' => 'Games', 'action' => 'view', $g->id]
                ),
                h($result ?? '-'),
                $this->scoreStr($g),
                $this->hrnLabel((int)($g->hrn ?? 0)),
                abs($ptsMur - $ptsOpp),
                h((string)($g->ot ?? '')),
                $ptsMur,
                $ptsOpp,
                h(($g->team_season->season->start ?? '') . '-' . ($g->team_season->season->end ?? '')),
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
     * @param int $id Game ID
     */
    public function view(int $id): void
    {
        $viewData = $this->buildGameViewData($id);

        $this->set($viewData);
    }

    /**
     * Render stats frame for Turbo requests.
     *
     * @param int $id Game ID
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
     * @param int $id Game ID
     * @return array<string,mixed>
     */
    private function buildGameViewData(int $id): array
    {
        try {
            $viewData = $this->gameViewService->getViewData($id);
        } catch (\Throwable $e) {
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
     * @param \App\Model\Entity\Game $game Game entity
     * @return void
     */
    private function enrichGameDisplay(object $game): void
    {
        try {
            $game->set('result_flag', $this->gameService->getResultFlag($game));
            $game->set('place_name', $this->gameService->getPlaceName($game));
            $game->set('place_state', $this->gameService->getPlaceState($game));
            $game->set('site_name', $this->gameService->getSiteName($game));
            $prefix = '@';
            if (!empty($game->hrn) && (int)$game->hrn === 1) {
                $prefix = 'Vs';
            } elseif (!empty($game->hrn) && (int)$game->hrn === 3) {
                $prefix = 'vs';
            }
            $game->set('opponent_prefix', $prefix);
        } catch (\Throwable $e) {
            // ignore enrichment errors
        }
    }
}
