<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Team;
use Cake\Routing\Router;

class StatsViewService
{
    /**
     * Create the service with its statistics provider.
     *
     * @param \App\Service\StatsService $statsService The statistics service.
     */
    public function __construct(private StatsService $statsService)
    {
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @param int $sportId The sport ID used to select stat fields.
     * @return array<int,array<int,mixed>>
     */
    public function playerSeasonRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $person = $row['person'] ?? null;
            $teamSeason = $row['teamSeason'] ?? null;
            $playerCell = $person
                ? $this->link(
                    $person->display ?? $person->label,
                    ['controller' => 'People', 'action' => 'view', $person->id],
                )
                : '-';
            $team = is_object($teamSeason) ? ($teamSeason->team ?? null) : null;

            $rows[] = array_merge(
                [
                    $playerCell,
                    h($this->formatTeamDisplayName($team)),
                    h(($teamSeason->season->start ?? '') . '-' . ($teamSeason->season->end ?? '')),
                ],
                $this->statsService->getPlayerSeasonStatCells($sportId, $stat),
            );
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @param int $sportId The sport ID used to select stat fields.
     * @return array<int,array<int,mixed>>
     */
    public function teamSeasonRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $teamSeason = $row['teamSeason'] ?? null;
            $seasonCell = $teamSeason
                ? $this->link(
                    ($teamSeason->season->start ?? '') . '-' . ($teamSeason->season->end ?? ''),
                    ['controller' => 'Seasons', 'action' => 'view', $teamSeason->id],
                )
                : '-';
            $team = is_object($teamSeason) ? ($teamSeason->team ?? null) : null;

            $rows[] = array_merge(
                [
                    h($this->formatTeamDisplayName($team)),
                    $seasonCell,
                ],
                $this->statsService->getTeamSeasonStatCells($sportId, $stat),
            );
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @param int $sportId The sport ID used to select stat fields.
     * @return array<int,array<int,mixed>>
     */
    public function playerCareerRows(array $results, int $sportId): array
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
     * @param array<int,array<string,mixed>> $results
     * @param int $sportId The sport ID used to select stat fields.
     * @return array<int,array<int,mixed>>
     */
    public function playerGameRows(array $results, int $sportId): array
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
                    $game->opponent?->getDisplayName() ?? 'vs ???',
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
     * @param array<int,array<string,mixed>> $results
     * @param int $sportId The sport ID used to select stat fields.
     * @param string $statType The requested team stat type.
     * @return array<int,array<int,mixed>>
     */
    public function teamGameRows(array $results, int $sportId, string $statType): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $game = $row['game'] ?? null;

            if ($statType === 'team-game') {
                $opponentText = ($game->team_season->team->abbr ?? '???') . ' Vs '
                    . ($game->opponent?->getDisplayName() ?? '???');
            } else {
                $opponentText = ($game->opponent?->getDisplayName() ?? '???') . ' Vs '
                    . ($game->team_season->team->abbr ?? '???');
            }

            $opponentCell = $game
                ? $this->link($opponentText, ['controller' => 'Games', 'action' => 'view', $game->id])
                : '-';
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
     * @param array<int,array<string,mixed>> $results
     * @param int $sportId The sport ID used to select stat fields.
     * @return array<int,array<int,mixed>>
     */
    public function opponentPlayerGameRows(array $results, int $sportId): array
    {
        $rows = [];
        foreach ($results as $row) {
            $stat = $row['stat'];
            $game = $row['game'] ?? null;
            $nameCell = h($this->statsService->getOpponentPlayerName($sportId, $stat)) ?: '-';
            $opponentCell = $game
                ? $this->link(
                    $game->opponent?->getDisplayName() ?? 'vs ???',
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

    /**
     * Format the display name for a team-like value.
     *
     * @param mixed $team The team or team-like value.
     * @return string
     */
    private function formatTeamDisplayName(mixed $team): string
    {
        if ($team instanceof Team) {
            return $team->getTeamDisplayName();
        }

        return is_object($team) ? (string)($team->team_name ?? '-') : '-';
    }

    /**
     * Build an escaped HTML link for a view cell.
     *
     * @param string $text The link text.
     * @param array<int|string,mixed> $url The CakePHP URL parameters.
     * @return string
     */
    private function link(string $text, array $url): string
    {
        return '<a href="' . h(Router::url($url)) . '">' . h($text) . '</a>';
    }
}
