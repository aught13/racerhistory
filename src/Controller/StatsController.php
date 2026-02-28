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
 *   - opponentPlayerGame: opponent player game stats
 *   - season: legacy single-season player stats view
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
        'team-season' => 'Team Season',
        'team-season-opponent' => 'Team Season Opponent',
        'player-career' => 'Player Career',
        'player-game' => 'Player Game',
        'team-game' => 'Team Game',
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
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
    }

    /**
     * Push stat types to every view for the sub-nav element.
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
     * @param array $rows Flat row arrays
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
     * @param string $text Display text (will be escaped)
     * @param array $url CakePHP URL array
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

            return $this->jsonResponse($this->formatPlayerSeasonRows($results));
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

            return $this->jsonResponse($this->formatTeamSeasonRows($results));
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

            return $this->jsonResponse($this->formatTeamSeasonRows($results));
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

            return $this->jsonResponse($this->formatPlayerCareerRows($results));
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

            return $this->jsonResponse($this->formatPlayerGameRows($results));
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

            return $this->jsonResponse($this->formatTeamGameRows($results));
        }

        $this->set('statType', 'team-game');
        $this->set('statTypeLabel', $this->statTypes['team-game']);
        $this->set('currentSport', $currentSport);

        return null;
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

            return $this->jsonResponse($this->formatOpponentPlayerGameRows($results));
        }

        $this->set('statType', 'opponent-player-game');
        $this->set('statTypeLabel', $this->statTypes['opponent-player-game']);
        $this->set('currentSport', $currentSport);

        return null;
    }

    /**
     * Legacy season stats view.
     *
     * @param int $teamSeasonId Team season ID
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

        try {
            $statsTable = $this->fetchTable('StatBasketSeasonPersons');
            $playerStats = $statsTable->find()
                ->contain(['Persons'])
                ->where(['StatBasketSeasonPersons.team_season_id' => $teamSeasonId])
                ->orderByDesc('StatBasketSeasonPersons.pts')
                ->all()
                ->toArray();
        } catch (\Exception $e) {
            $playerStats = [];
        }

        $this->set(compact('teamSeason', 'playerStats'));

        return null;
    }

    /**
     * Format player season results for DataTables JSON.
     *
     * @param array $results Service results
     * @return array<int, array>
     */
    protected function formatPlayerSeasonRows(array $results): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $person = $row['person'] ?? null;
            $ts = $row['teamSeason'] ?? null;

            $playerCell = $person
                ? $this->link(
                    $person->display ?? $person->label,
                    ['controller' => 'People', 'action' => 'view', $person->id]
                )
                : '-';

            $rows[] = [
                $playerCell,
                h($ts->team->team_name ?? '-'),
                h(($ts->season->start ?? '') . '-' . ($ts->season->end ?? '')),
                (int)($stat->GP ?? 0),
                (int)($stat->GS ?? 0),
                (int)($stat->MIN ?? 0),
                (int)($stat->FGM ?? 0),
                (int)($stat->FGA ?? 0),
                (int)($stat->TPM ?? 0),
                (int)($stat->TPA ?? 0),
                (int)($stat->FTM ?? 0),
                (int)($stat->FTA ?? 0),
                (int)($stat->ORB ?? 0),
                (int)($stat->DRB ?? 0),
                (int)($stat->RB ?? 0),
                (int)($stat->AST ?? 0),
                (int)($stat->STL ?? 0),
                (int)($stat->BS ?? 0),
                (int)($stat->TRN ?? 0),
                (int)($stat->PF ?? 0),
                (int)($stat->PTS ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Format team season (and opponent) results for DataTables JSON.
     *
     * @param array $results Service results
     * @return array<int, array>
     */
    protected function formatTeamSeasonRows(array $results): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $ts = $row['teamSeason'] ?? null;

            $seasonCell = $ts
                ? $this->link(
                    ($ts->season->start ?? '') . '-' . ($ts->season->end ?? ''),
                    ['controller' => 'Seasons', 'action' => 'view', $ts->id]
                )
                : '-';

            $rows[] = [
                h($ts->team->team_name ?? '-'),
                $seasonCell,
                (int)($stat->GP ?? 0),
                (int)($stat->MIN ?? 0),
                (int)($stat->FGM ?? 0),
                (int)($stat->FGA ?? 0),
                (int)($stat->TPM ?? 0),
                (int)($stat->TPA ?? 0),
                (int)($stat->FTM ?? 0),
                (int)($stat->FTA ?? 0),
                (int)($stat->ORB ?? 0),
                (int)($stat->DRB ?? 0),
                (int)($stat->RB ?? 0),
                (int)($stat->AST ?? 0),
                (int)($stat->STL ?? 0),
                (int)($stat->BS ?? 0),
                (int)($stat->TRN ?? 0),
                (int)($stat->PF ?? 0),
                (int)($stat->PTS ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Format player career results for DataTables JSON.
     *
     * @param array $results Service results
     * @return array<int, array>
     */
    protected function formatPlayerCareerRows(array $results): array
    {
        $rows = [];
        foreach ($results as $row) {
            $person = $row['person'] ?? null;
            $totals = $row['totals'];
            $seasons = $row['seasons'];

            $playerCell = $person
                ? $this->link(
                    $person->display ?? $person->label,
                    ['controller' => 'People', 'action' => 'view', $person->id]
                )
                : '-';

            $rows[] = [
                $playerCell,
                (int)$seasons,
                (int)($totals['GP'] ?? 0),
                (int)($totals['GS'] ?? 0),
                (int)($totals['MIN'] ?? 0),
                (int)($totals['FGM'] ?? 0),
                (int)($totals['FGA'] ?? 0),
                (int)($totals['TPM'] ?? 0),
                (int)($totals['TPA'] ?? 0),
                (int)($totals['FTM'] ?? 0),
                (int)($totals['FTA'] ?? 0),
                (int)($totals['ORB'] ?? 0),
                (int)($totals['DRB'] ?? 0),
                (int)($totals['RB'] ?? 0),
                (int)($totals['AST'] ?? 0),
                (int)($totals['STL'] ?? 0),
                (int)($totals['BS'] ?? 0),
                (int)($totals['TRN'] ?? 0),
                (int)($totals['PF'] ?? 0),
                (int)($totals['PTS'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Format player game results for DataTables JSON.
     *
     * @param array $results Service results
     * @return array<int, array>
     */
    protected function formatPlayerGameRows(array $results): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $person = $row['person'] ?? null;
            $game = $row['game'] ?? null;

            $playerCell = $person
                ? $this->link(
                    $person->display ?? $person->label,
                    ['controller' => 'People', 'action' => 'view', $person->id]
                )
                : '-';

            $opponentCell = $game
                ? $this->link(
                    $game->opponent->opponent_short ?? $game->opponent->opponent_name ?? 'vs ???',
                    ['controller' => 'Games', 'action' => 'view', $game->id]
                )
                : '-';

            $rows[] = [
                $playerCell,
                $opponentCell,
                h((string)($game->game_date ?? '-')),
                (int)($stat->GP ?? 0),
                (int)($stat->GS ?? 0),
                (int)($stat->MIN ?? 0),
                (int)($stat->FGM ?? 0),
                (int)($stat->FGA ?? 0),
                (int)($stat->TPM ?? 0),
                (int)($stat->TPA ?? 0),
                (int)($stat->FTM ?? 0),
                (int)($stat->FTA ?? 0),
                (int)($stat->ORB ?? 0),
                (int)($stat->DRB ?? 0),
                (int)($stat->RB ?? 0),
                (int)($stat->AST ?? 0),
                (int)($stat->STL ?? 0),
                (int)($stat->BS ?? 0),
                (int)($stat->TRN ?? 0),
                (int)($stat->PF ?? 0),
                (int)($stat->PTS ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Format team game box score results for DataTables JSON.
     *
     * @param array $results Service results
     * @return array<int, array>
     */
    protected function formatTeamGameRows(array $results): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $game = $row['game'] ?? null;

            $opponentCell = $game
                ? $this->link(
                    $game->opponent->opponent_short ?? $game->opponent->opponent_name ?? 'vs ???',
                    ['controller' => 'Games', 'action' => 'view', $game->id]
                )
                : '-';

            $rows[] = [
                $opponentCell,
                h((string)($game->game_date ?? '-')),
                (int)($stat->FGM ?? 0),
                (int)($stat->FGA ?? 0),
                (int)($stat->TPM ?? 0),
                (int)($stat->TPA ?? 0),
                (int)($stat->FTM ?? 0),
                (int)($stat->FTA ?? 0),
                (int)($stat->ORB ?? 0),
                (int)($stat->DRB ?? 0),
                (int)($stat->RB ?? 0),
                (int)($stat->AST ?? 0),
                (int)($stat->STL ?? 0),
                (int)($stat->BS ?? 0),
                (int)($stat->TRN ?? 0),
                (int)($stat->PF ?? 0),
                (int)($stat->PTS ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Format opponent player game results for DataTables JSON.
     *
     * @param array $results Service results
     * @return array<int, array>
     */
    protected function formatOpponentPlayerGameRows(array $results): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $game = $row['game'] ?? null;

            $opponentCell = $game
                ? $this->link(
                    $game->opponent->opponent_short ?? $game->opponent->opponent_name ?? 'vs ???',
                    ['controller' => 'Games', 'action' => 'view', $game->id]
                )
                : '-';

            $rows[] = [
                h((string)($stat->name ?? '-')),
                $opponentCell,
                h((string)($game->game_date ?? '-')),
                (int)($stat->MIN ?? 0),
                (int)($stat->FGM ?? 0),
                (int)($stat->FGA ?? 0),
                (int)($stat->TPM ?? 0),
                (int)($stat->TPA ?? 0),
                (int)($stat->FTM ?? 0),
                (int)($stat->FTA ?? 0),
                (int)($stat->ORB ?? 0),
                (int)($stat->DRB ?? 0),
                (int)($stat->RB ?? 0),
                (int)($stat->AST ?? 0),
                (int)($stat->STL ?? 0),
                (int)($stat->BS ?? 0),
                (int)($stat->TRN ?? 0),
                (int)($stat->PF ?? 0),
                (int)($stat->PTS ?? 0),
            ];
        }

        return $rows;
    }
}
