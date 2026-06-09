<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\StatsService;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * Public Stats Controller
 *
 * Displays searchable statistics across all sports with stat support.
 * Currently fully implemented for basketball.
 *
 * Actions:
 *   - index: landing page with stat type navigation
 *   - playerSeason: player season stats search
 *   - teamSeason: team season totals search
 *   - teamSeasonOpponent: opponent season totals search
 *   - playerCareer: career aggregated stats
 *   - playerGame: individual player game stats
 *   - opponentTeamGame: opponent team game box score stats
 *   - opponentPlayerGame: opponent player game stats
 *   - season: legacy single-season player stats view
 * All search actions support JSON responses for DataTables integration, as well as standard HTML views.
 * The season action is a legacy view that shows player stats for a specific team season and is not designed for DataTables.
 *
 * Security:
 * - All actions skip authorization to allow public access to stats information.
 * - The season action checks for the existence of the team season and redirects with an error message
 * if it does not exist, preventing access to invalid team season IDs.
 *
 * Dependencies:
 * - StatsService: Provides methods for retrieving and formatting various types of sports statistics, including player
 * season stats, team season stats, player career stats, and game stats. The service abstracts the data retrieval logic
 * and allows the controller to focus on request handling and response formatting.
 *
 * Components:
 * - AuthorizationComponent: Used to skip authorization checks for all actions in this controller, as the
 * stats information is intended to be publicly accessible.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class StatsController extends AppController
{
    /**
     * @var \App\Service\StatsService
     */
    protected StatsService $statsService;

    /**
     * Allowed stat types and their labels.
     *
     * @var array<string, string>
     */
    protected array $statTypes = [
        'player-season' => 'Player Season',
        'player-career' => 'Player Career',
        'team-season' => 'Team Season',
        'team-season-opponent' => 'Team Season Opponent',
        'team-game' => 'Team Game',
        'opponent-team-game' => 'Opponent Team Game',
        'player-game' => 'Player Game',
        'opponent-player-game' => 'Opponent Player Game',
    ];

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->statsService = new StatsService();
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
     * Push stat types to every view for the sub-nav element.
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $this->set('statTypes', $this->statTypes);
    }

    /**
     * Get common filter data from query params.
     *
     * @return array
     */
    protected function getFilters(): array
    {
        $filters = [];
        $request = $this->getRequest();

        $seasonId = $request->getQuery('season_id');
        if ($seasonId !== null && $seasonId !== '') {
            $filters['season_id'] = (int)$seasonId;
        }

        $teamId = $request->getQuery('team_id');
        if ($teamId !== null && $teamId !== '') {
            $filters['team_id'] = (int)$teamId;
        }

        $gameId = $request->getQuery('game_id');
        if ($gameId !== null && $gameId !== '') {
            $filters['game_id'] = (int)$gameId;
        }

        return $filters;
    }

    /**
     * Check if the current request expects a JSON response.
     *
     * @return bool
     */
    protected function isJsonRequest(): bool
    {
        return $this->getRequest()->is('ajax')
            || $this->getRequest()->getQuery('format') === 'json';
    }

    /**
     * Return a JSON response with DataTables-compatible data array.
     *
     * @param array $rows
     * @return \Cake\Http\Response
     */
    protected function jsonResponse(array $rows): Response
    {
        return $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode(['data' => $rows]));
    }

    /**
     * Build a safe HTML anchor tag.
     *
     * @param string $text
     * @param array $url
     * @return string
     */
    protected function link(string $text, array $url): string
    {
        return '<a href="' . h(Router::url($url)) . '">' . h($text) . '</a>';
    }

    /**
     * Resolve sport ID from query param, defaulting to basketball.
     *
     * @return int
     */
    protected function resolveSportId(): int
    {
        $sport = $this->getRequest()->getQuery('sport', 'basketball');
        $sportId = $this->statsService->getSportIdByName((string)$sport);

        return $sportId ?? 1;
    }

    /**
     * Stats index/landing page.
     */
    public function index(): void
    {
        $this->set('currentSport', $this->getRequest()->getQuery('sport', 'basketball'));
    }

    /**
     * Player season stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function playerSeason(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchPlayerSeasonStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatPlayerSeasonRows($results, $sportId));
        }

        $this->set('statType', 'player-season');
        $this->set('statTypeLabel', $this->statTypes['player-season']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Team season stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function teamSeason(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchTeamSeasonStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatTeamSeasonRows($results, $sportId));
        }

        $this->set('statType', 'team-season');
        $this->set('statTypeLabel', $this->statTypes['team-season']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Team season opponent stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function teamSeasonOpponent(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchTeamSeasonOpponentStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatTeamSeasonRows($results, $sportId));
        }

        $this->set('statType', 'team-season-opponent');
        $this->set('statTypeLabel', $this->statTypes['team-season-opponent']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Player career stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function playerCareer(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchPlayerCareerStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatPlayerCareerRows($results, $sportId));
        }

        $this->set('statType', 'player-career');
        $this->set('statTypeLabel', $this->statTypes['player-career']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Player game stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function playerGame(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchPlayerGameStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatPlayerGameRows($results, $sportId));
        }

        $this->set('statType', 'player-game');
        $this->set('statTypeLabel', $this->statTypes['player-game']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Team game box score stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function teamGame(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchTeamGameStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatTeamGameRows($results, $sportId, 'team-game'));
        }

        $this->set('statType', 'team-game');
        $this->set('statTypeLabel', $this->statTypes['team-game']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Opponent team game box score stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function opponentTeamGame(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchOpponentTeamGameStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatTeamGameRows($results, $sportId, 'opponent-team-game'));
        }

        $this->set('statType', 'opponent-team-game');
        $this->set('statTypeLabel', $this->statTypes['opponent-team-game']);
        $this->set('currentSport', $currentSport);

        return $this->render('team_game');
    }

    /**
     * Opponent player game stats search.
     *
     * @return \Cake\Http\Response|null
     */
    public function opponentPlayerGame(): ?Response
    {
        $sportId = $this->resolveSportId();
        $currentSport = (string)$this->getRequest()->getQuery('sport', 'basketball');

        if ($this->isJsonRequest()) {
            $results = $this->statsService->searchOpponentPlayerGameStats($sportId, ['limit' => 0]);

            return $this->jsonResponse($this->formatOpponentPlayerGameRows($results, $sportId));
        }

        $this->set('statType', 'opponent-player-game');
        $this->set('statTypeLabel', $this->statTypes['opponent-player-game']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Legacy season stats view.
     *
     * @param int $teamSeasonId
     * @return \Cake\Http\Response|null
     */
    public function season(int $teamSeasonId)
    {
        $teamSeasonsTable = $this->fetchTable('TeamSeasons');
        $teamSeason = $teamSeasonsTable->find()
            ->contain(['Teams', 'Seasons'])
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->first();

        if (!$teamSeason) {
            $this->Flash->error('Season not found');

            return $this->redirect(['action' => 'index']);
        }

        $playerStats = $this->statsService->getSeasonPlayerStatsList($teamSeasonId);

        $this->set(compact('teamSeason', 'playerStats'));

        return null;
    }

    /**
     * Format player season results for DataTables JSON.
     *
     * @param array $results
     * @param int $sportId
     * @return array<int, array>
     */
    protected function formatPlayerSeasonRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $person = $row['person'] ?? null;
            $ts = $row['teamSeason'] ?? null;

            $playerCell = $person
                ? $this->link(
                    $person->display ?? $person->label,
                    ['controller' => 'People', 'action' => 'view', $person->id],
                )
                : '-';

            $rows[] = array_merge(
                [
                    $playerCell,
                    h($ts->team->team_name ?? '-'),
                    h(($ts->season->start ?? '') . '-' . ($ts->season->end ?? '')),
                ],
                $this->statsService->getPlayerSeasonStatCells($sportId, $stat),
            );
        }

        return $rows;
    }

    /**
     * Format team season (and opponent) results for DataTables JSON.
     *
     * @param array $results
     * @param int $sportId
     * @return array<int, array>
     */
    protected function formatTeamSeasonRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $ts = $row['teamSeason'] ?? null;

            $seasonCell = $ts
                ? $this->link(
                    ($ts->season->start ?? '') . '-' . ($ts->season->end ?? ''),
                    ['controller' => 'Seasons', 'action' => 'view', $ts->id],
                )
                : '-';

            $rows[] = array_merge(
                [
                    h($ts->team->team_name ?? '-'),
                    $seasonCell,
                ],
                $this->statsService->getTeamSeasonStatCells($sportId, $stat),
            );
        }

        return $rows;
    }

    /**
     * Format player career results for DataTables JSON.
     *
     * @param array $results
     * @param int $sportId
     * @return array<int, array>
     */
    protected function formatPlayerCareerRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $person = $row['person'] ?? null;
            $totals = $row['totals'];
            $seasons = $row['seasons'];

            $playerCell = $person
                ? $this->link(
                    $person->display ?? $person->label,
                    ['controller' => 'People', 'action' => 'view', $person->id],
                )
                : '-';

            $rows[] = array_merge(
                [$playerCell, (int)$seasons],
                $this->statsService->getPlayerCareerStatCells($sportId, $totals),
            );
        }

        return $rows;
    }

    /**
     * Format player game results for DataTables JSON.
     *
     * @param array $results
     * @param int $sportId
     * @return array<int, array>
     */
    protected function formatPlayerGameRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $person = $row['person'] ?? null;
            $game = $row['game'] ?? null;

            $playerCell = $person
                ? $this->link(
                    $person->display ?? $person->label,
                    ['controller' => 'People', 'action' => 'view', $person->id],
                )
                : '-';

            $opponentCell = $game
                ? $this->link(
                    $game->opponent->opponent_short ?? $game->opponent->opponent_name ?? 'vs ???',
                    ['controller' => 'Games', 'action' => 'view', $game->id],
                )
                : '-';

            $rows[] = array_merge(
                [
                    $playerCell,
                    $opponentCell,
                    h((string)($game->game_date ?? '-')),
                ],
                $this->statsService->getPlayerGameStatCells($sportId, $stat),
            );
        }

        return $rows;
    }

    /**
     * Format team game box score results for DataTables JSON.
     *
     * @param array $results
     * @param int $sportId
     * @param string $statType
     * @return array<int, array>
     */
    protected function formatTeamGameRows(array $results, int $sportId, string $statType): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $game = $row['game'] ?? null;

            if ($statType === 'team-game') {
                $opponentText = ($game->team_season->team->abbr ?? '???') . ' Vs ' .
                    ($game->opponent->opponent_short ?? $game->opponent->opponent_name ?? '???');
            } else {
                $opponentText = ($game->opponent->opponent_short ?? $game->opponent->opponent_name ?? '???') . ' Vs ' .
                    ($game->team_season->team->abbr ?? '???');
            }

            $opponentCell = $game ? $this->link(
                $opponentText,
                ['controller' => 'Games', 'action' => 'view', $game->id],
            ) : '-';

            $rows[] = array_merge(
                [
                    $opponentCell,
                    h((string)($game->game_date ?? '-')),
                ],
                $this->statsService->getTeamGameStatCells($sportId, $stat),
            );
        }

        return $rows;
    }

    /**
     * Format opponent player game results for DataTables JSON.
     *
     * @param array $results
     * @param int $sportId
     * @return array<int, array>
     */
    protected function formatOpponentPlayerGameRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $game = $row['game'] ?? null;

            $nameCell = h($this->statsService->getOpponentPlayerName($sportId, $stat)) ?: '-';

            $opponentCell = $game
                ? $this->link(
                    $game->opponent->opponent_short ?? $game->opponent->opponent_name ?? 'vs ???',
                    ['controller' => 'Games', 'action' => 'view', $game->id],
                )
                : '-';

            $rows[] = array_merge(
                [
                    $nameCell,
                    $opponentCell,
                    h((string)($game->game_date ?? '-')),
                ],
                $this->statsService->getOpponentPlayerGameStatCells($sportId, $stat),
            );
        }

        return $rows;
    }
}
