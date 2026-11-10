<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use Cake\I18n\Date;
use Cake\Routing\Router;
use DateTimeInterface;

class GameListViewService
{
    /**
     * Create the service with its game data provider.
     *
     * @param \App\Service\GameService $gameService The game service.
     */
    public function __construct(private GameService $gameService)
    {
    }

    /**
     * @param array<int,\App\Model\Entity\Game> $games
     * @param array{sport:string,team:string} $scopeQuery
     * @return array<int,array<int,string|int>>
     */
    public function rankedRows(array $games, array $scopeQuery): array
    {
        $rows = [];
        foreach ($games as $game) {
            $result = $this->gameService->getResultFlag($game);
            $pointsFor = (int)($game->pts_mur ?? 0);
            $pointsAgainst = (int)($game->pts_opp ?? 0);
            $seasonLabel = ($game->team_season->season->start ?? '') . '-'
                . ($game->team_season->season->end ?? '');
            $dateDisplay = $this->formatDate($game->game_date);
            $rows[] = [
                $this->link(
                    $dateDisplay,
                    ['controller' => 'Games', 'action' => 'view', $game->id],
                    $scopeQuery,
                    ['escape' => false],
                ),
                (int)($game->mur_rk ?? 0) ?: '-',
                h($game->team_season->team?->getDisplayName() ?? 'Murray State'),
                $this->link(
                    $game->opponent?->getDisplayName() ?? '?',
                    [
                        'controller' => 'Games',
                        'action' => 'series',
                        '?' => ['opponent_id' => $game->opponent->id ?? 0],
                    ],
                    $scopeQuery,
                ),
                (int)($game->opp_rk ?? 0) ?: '-',
                GameSearchService::hrnLabel((int)($game->hrn ?? 0)),
                h($result ?? '-'),
                abs($pointsFor - $pointsAgainst),
                $pointsFor,
                $pointsAgainst,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $game->team_season->id ?? 0],
                    $scopeQuery,
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int,\App\Model\Entity\Game> $games
     * @param array{sport:string,team:string} $scopeQuery
     * @param string $dateFormat The date format for displayed dates.
     * @param bool $showConferenceTypeAbr Whether to show conference abbreviations.
     * @param bool $includeWeekdayColumn Whether to include a weekday column.
     * @param bool $includeTeamColumn Whether to include a team column.
     * @return array<int,array<int,string|int>>
     */
    public function overtimeRows(
        array $games,
        array $scopeQuery,
        string $dateFormat = 'm/d/Y',
        bool $showConferenceTypeAbr = false,
        bool $includeWeekdayColumn = false,
        bool $includeTeamColumn = false,
    ): array {
        $rows = [];
        foreach ($games as $game) {
            $result = $this->gameService->getResultFlag($game);
            $pointsFor = (int)($game->pts_mur ?? 0);
            $pointsAgainst = (int)($game->pts_opp ?? 0);
            $isConference = (bool)($game->game_type->conf ?? false);
            $seasonLabel = ($game->team_season->season->start ?? '') . '-'
                . ($game->team_season->season->end ?? '');
            $dateDisplay = $this->formatDate($game->game_date, $dateFormat);
            $gameTypeDisplay = $game->post || ($showConferenceTypeAbr && $isConference)
                ? h((string)($game->game_type->abr ?? ($isConference ? 'Conf' : 'Post')))
                : 'Regular';
            $row = [
                $this->link(
                    $dateDisplay,
                    ['controller' => 'Games', 'action' => 'view', $game->id],
                    $scopeQuery,
                    ['escape' => false],
                ),
            ];
            if ($includeTeamColumn) {
                $row[] = h($game->team_season->team->abbr ?? '-');
            }
            $row[] = $this->link(
                $game->opponent->opponent_abbr ?? '?',
                [
                    'controller' => 'Games',
                    'action' => 'series',
                    '?' => ['opponent_id' => $game->opponent->id ?? 0],
                ],
                $scopeQuery,
            );
            $row = array_merge($row, [
                h($result ?? '-'),
                abs($pointsFor - $pointsAgainst),
                $pointsFor,
                $pointsAgainst,
                $this->otDisplay($game->ot ?? null),
                GameSearchService::hrnLabel((int)($game->hrn ?? 0)),
                $isConference ? 'Y' : 'N',
                $gameTypeDisplay,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $game->team_season->id ?? 0],
                    $scopeQuery,
                ),
            ]);
            if ($includeWeekdayColumn) {
                $row[] = $this->formatWeekday($game->game_date ?? null);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int,\App\Model\Entity\Game> $games
     * @param array{sport:string,team:string} $scopeQuery
     * @return array<int,array<int,string|int>>
     */
    public function hundredPointRows(array $games, array $scopeQuery): array
    {
        $rows = [];
        foreach ($games as $game) {
            $result = $this->gameService->getResultFlag($game);
            $pointsFor = (int)($game->pts_mur ?? 0);
            $pointsAgainst = (int)($game->pts_opp ?? 0);
            $isConference = (bool)($game->game_type->conf ?? false);
            $seasonLabel = ($game->team_season->season->start ?? '') . '-'
                . ($game->team_season->season->end ?? '');
            $gameTypeDisplay = $game->post
                ? h((string)($game->game_type->abr ?? 'Post'))
                : 'Regular';
            $opponentDisplay = $game->opponent?->getDisplayName() ?? '?';
            $rows[] = [
                $this->link(
                    $this->formatDate($game->game_date),
                    ['controller' => 'Games', 'action' => 'view', $game->id],
                    $scopeQuery,
                    ['escape' => false],
                ),
                $this->link(
                    $opponentDisplay,
                    [
                        'controller' => 'Games',
                        'action' => 'series',
                        '?' => ['opponent_id' => $game->opponent->id ?? 0],
                    ],
                    $scopeQuery,
                ),
                h($result ?? '-'),
                abs($pointsFor - $pointsAgainst),
                $pointsFor,
                $pointsAgainst,
                $this->otDisplay($game->ot ?? null),
                GameSearchService::hrnLabel((int)($game->hrn ?? 0)),
                $isConference ? 'Y' : 'N',
                $gameTypeDisplay,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $game->team_season->id ?? 0],
                    $scopeQuery,
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int,\App\Model\Entity\Game> $games
     * @param array{sport:string,team:string} $scopeQuery
     * @return array<int,array<int,string|int>>
     */
    public function openerRows(array $games, array $scopeQuery): array
    {
        $rows = [];
        foreach ($games as $game) {
            $result = $this->gameService->getResultFlag($game);
            $pointsFor = (int)($game->pts_mur ?? 0);
            $pointsAgainst = (int)($game->pts_opp ?? 0);
            $isConference = (bool)($game->game_type->conf ?? false);
            $seasonLabel = ($game->team_season->season->start ?? '') . '-'
                . ($game->team_season->season->end ?? '');
            $gameTypeDisplay = $game->post || $isConference
                ? h((string)($game->game_type->abr ?? ($isConference ? 'Conf' : 'Post')))
                : 'Regular';
            $opponentDisplay = $game->opponent?->getDisplayName() ?? '?';
            $rows[] = [
                $this->link(
                    $this->formatDate($game->game_date),
                    ['controller' => 'Games', 'action' => 'view', $game->id],
                    $scopeQuery,
                    ['escape' => false],
                ),
                $this->link(
                    $opponentDisplay,
                    [
                        'controller' => 'Games',
                        'action' => 'series',
                        '?' => ['opponent_id' => $game->opponent->id ?? 0],
                    ],
                    $scopeQuery,
                ),
                h($result ?? '-'),
                abs($pointsFor - $pointsAgainst),
                $pointsFor,
                $pointsAgainst,
                $this->otDisplay($game->ot ?? null),
                GameSearchService::hrnLabel((int)($game->hrn ?? 0)),
                $isConference ? 'Y' : 'N',
                $gameTypeDisplay,
                $this->link(
                    $seasonLabel,
                    ['controller' => 'Seasons', 'action' => 'view', $game->team_season->id ?? 0],
                    $scopeQuery,
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int,\App\Model\Entity\Game> $games
     * @param array{sport:string,team:string} $scopeQuery
     * @return array<int,array<int,string|int>>
     */
    public function seriesRows(array $games, array $scopeQuery): array
    {
        $rows = [];
        foreach ($games as $game) {
            $result = $game->result_flag ?? $this->gameService->getResultFlag($game);
            $pointsFor = (int)($game->pts_mur ?? 0);
            $pointsAgainst = (int)($game->pts_opp ?? 0);
            $rows[] = [
                $this->formatDate($game->game_date),
                h($result ?? '-'),
                $this->scoreString($game),
                GameSearchService::hrnLabel((int)($game->hrn ?? 0)),
                abs($pointsFor - $pointsAgainst),
                $pointsFor,
                $pointsAgainst,
                h(($game->team_season->season->start ?? '') . '-' . ($game->team_season->season->end ?? '')),
                $this->link(
                    'View',
                    ['controller' => 'Games', 'action' => 'view', $game->id],
                    $scopeQuery,
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array{sport:string,team:string} $scopeQuery
     * @return array<int,array<int,string|int>>
     */
    public function seriesOpponentRows(array $rows, array $scopeQuery): array
    {
        return array_map(function (array $row) use ($scopeQuery): array {
            $opponentId = (int)($row['opponent_id'] ?? 0);

            return [
                h((string)($row['opponent_display_name'] ?? $row['opponent_name'] ?? 'Unknown')),
                h((string)($row['opponent_short'] ?? '-')),
                (int)($row['games_count'] ?? 0),
                $this->link(
                    'View Series',
                    [
                        'controller' => 'Games',
                        'action' => 'series-history',
                        '?' => ['opponent_id' => $opponentId],
                    ],
                    $scopeQuery,
                ),
            ];
        }, $rows);
    }

    /**
     * @param array<int,\App\Model\Entity\Game> $games
     * @return array<int,\App\Model\Entity\Game>
     */
    public function enrichGames(array $games): array
    {
        foreach ($games as $game) {
            $this->enrichGameDisplay($game);
            if (!$game->has('margin')) {
                $pointsFor = (int)($game->pts_mur ?? 0);
                $pointsAgainst = (int)($game->pts_opp ?? 0);
                $game->set('margin', abs($pointsFor - $pointsAgainst));
            }
        }

        return $games;
    }

    /**
     * Add display fields used by game templates to an entity.
     *
     * @param \App\Model\Entity\Game $game The game to enrich.
     * @return void
     */
    public function enrichGameDisplay(Game $game): void
    {
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
    }

    /**
     * Build a game link with the current public scope.
     *
     * @param string $text The link text.
     * @param array<int|string,mixed> $url The CakePHP URL parameters.
     * @param array{sport:string,team:string} $scopeQuery The public scope parameters.
     * @param array<string,mixed> $options The link rendering options.
     * @return string
     */
    private function link(string $text, array $url, array $scopeQuery, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $display = $escape ? h($text) : $text;
        $url['?'] = array_merge((array)($url['?'] ?? []), $scopeQuery);

        return '<a href="' . h(Router::url($url)) . '">' . $display . '</a>';
    }

    /**
     * Format a date inside an accessible time element.
     *
     * @param mixed $date The date value to display.
     * @param string $displayFormat The display format.
     * @return string
     */
    private function formatDate(mixed $date, string $displayFormat = 'm/d/Y'): string
    {
        if ($date === null) {
            return '-';
        }
        if ($date instanceof Date || $date instanceof DateTimeInterface) {
            $iso = $date->format('Y-m-d');
            $display = $date->format($displayFormat);
        } else {
            $iso = (string)$date;
            $timestamp = strtotime($iso);
            $display = $timestamp ? date($displayFormat, $timestamp) : $iso;
        }

        return '<time class="rh-game-date" datetime="' . h($iso) . '">' . h($display) . '</time>';
    }

    /**
     * Format the weekday name for a date value.
     *
     * @param mixed $date The date value to display.
     * @return string
     */
    private function formatWeekday(mixed $date): string
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
     * Format a game's score as a display string.
     *
     * @param \App\Model\Entity\Game $game The game to format.
     * @return string
     */
    private function scoreString(Game $game): string
    {
        return ($game->pts_mur ?? '-') . '-' . ($game->pts_opp ?? '-');
    }

    /**
     * Format an overtime value for display.
     *
     * @param mixed $overtime The overtime value.
     * @return string
     */
    private function otDisplay(mixed $overtime): string
    {
        if ($overtime === null) {
            return '-';
        }

        $value = trim((string)$overtime);
        if ($value === '' || $value === '0') {
            return '-';
        }

        return h($value);
    }
}
