<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\SeasonViewService;
use App\Service\TeamSeasonService;

class TeamSeasonsController extends AppController
{
    private TeamSeasonService $teamSeasonService;
    private SeasonViewService $seasonViewService;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->teamSeasonService = new TeamSeasonService();
        $this->seasonViewService = new SeasonViewService($this->teamSeasonService);
    }

    /**
     * List team seasons (supports ?include=details).
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $limit = $this->getLimit(200, 500);
        $include = trim((string)$this->getRequest()->getQuery('include', ''));

        if ($include === 'details') {
            $rows = $this->teamSeasonService->getAllTeamSeasons();
            $rows = array_slice($rows, 0, $limit);

            $data = [];
            foreach ($rows as $ts) {
                $data[] = [
                    'id' => (int)$ts->id,
                    'label' => $this->teamSeasonService->getSportDisplayLabel((int)$ts->id),
                    'team_id' => $ts->team_id ?? null,
                    'season_id' => $ts->season_id ?? null,
                    'team' => [
                        'id' => $ts->team->id ?? null,
                        'team_name' => $ts->team->team_name ?? null,
                        'gender' => $ts->team->gender ?? null,
                        'sport' => [
                            'id' => $ts->team->sport->id ?? null,
                            'sport_name' => $ts->team->sport->sport_name ?? null,
                        ],
                    ],
                    'season' => [
                        'id' => $ts->season->id ?? null,
                        'start' => $ts->season->start ?? null,
                        'end' => $ts->season->end ?? null,
                    ],
                ];
            }

            $this->respond([
                'data' => $data,
                'meta' => [
                    'count' => count($data),
                    'limit' => $limit,
                ],
            ]);

            return;
        }

        $rows = $this->teamSeasonService->getTeamSeasonsForSelect();
        $data = array_slice($rows, 0, $limit);

        $this->respond([
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Get a single team season.
     */
    public function view(int $id): void
    {
        $this->request->allowMethod(['get']);

        $include = trim((string)$this->getRequest()->getQuery('include', ''));
        if ($include === 'details') {
            $viewData = $this->seasonViewService->getViewData($id);
            $teamSeason = $viewData['teamSeason'] ?? null;
            if (!$teamSeason) {
                $this->respondError('Team season not found', 404);

                return;
            }

            $this->respond([
                'data' => [
                    'id' => (int)$teamSeason->id,
                    'label' => $this->teamSeasonService->getDisplayLabel((int)$teamSeason->id),
                    'sport_label' => $this->teamSeasonService->getSportDisplayLabel((int)$teamSeason->id),
                    'team_id' => $teamSeason->team_id ?? null,
                    'season_id' => $teamSeason->season_id ?? null,
                    'team' => [
                        'id' => $teamSeason->team->id ?? null,
                        'team_name' => $teamSeason->team->team_name ?? null,
                        'gender' => $teamSeason->team->gender ?? null,
                        'sport' => [
                            'id' => $teamSeason->team->sport->id ?? null,
                            'sport_name' => $teamSeason->team->sport->sport_name ?? null,
                        ],
                    ],
                    'season' => [
                        'id' => $teamSeason->season->id ?? null,
                        'start' => $teamSeason->season->start ?? null,
                        'end' => $teamSeason->season->end ?? null,
                    ],
                    'record_summary' => $viewData['recordSummary'] ?? [],
                    'games' => $this->formatGames($viewData['games'] ?? []),
                    'roster' => $this->formatRoster($viewData['roster'] ?? []),
                    'posts' => [
                        'preview' => $this->formatPosts($viewData['previewPosts'] ?? []),
                        'review' => $this->formatPosts($viewData['reviewPosts'] ?? []),
                        'other' => $this->formatPosts($viewData['otherPosts'] ?? []),
                    ],
                ],
            ]);

            return;
        }

        $ts = $this->teamSeasonService->getTeamSeasonById($id);
        if (!$ts) {
            $this->respondError('Team season not found', 404);

            return;
        }

        $this->respond([
            'data' => [
                'id' => (int)$ts->id,
                'label' => $this->teamSeasonService->getDisplayLabel((int)$ts->id),
                'sport_label' => $this->teamSeasonService->getSportDisplayLabel((int)$ts->id),
                'team_id' => $ts->team_id ?? null,
                'season_id' => $ts->season_id ?? null,
                'team' => [
                    'id' => $ts->team->id ?? null,
                    'team_name' => $ts->team->team_name ?? null,
                    'gender' => $ts->team->gender ?? null,
                    'sport' => [
                        'id' => $ts->team->sport->id ?? null,
                        'sport_name' => $ts->team->sport->sport_name ?? null,
                    ],
                ],
                'season' => [
                    'id' => $ts->season->id ?? null,
                    'start' => $ts->season->start ?? null,
                    'end' => $ts->season->end ?? null,
                ],
            ],
        ]);
    }

    /**
     * @param array<int,\App\Model\Entity\Game> $games
     * @return array<int,array<string,mixed>>
     */
    private function formatGames(array $games): array
    {
        return array_map(static function ($game): array {
            return [
                'id' => (int)$game->id,
                'game_date' => $game->game_date ?? null,
                'result' => $game->result ?? null,
                'mur_pts' => $game->mur_pts ?? null,
                'opp_pts' => $game->opp_pts ?? null,
                'opponent' => [
                    'id' => $game->opponent->id ?? null,
                    'opponent_name' => $game->opponent->opponent_name ?? null,
                ],
                'place' => [
                    'id' => $game->place->id ?? null,
                    'city' => $game->place->city ?? null,
                    'state' => $game->place->state ?? null,
                ],
                'game_type' => [
                    'id' => $game->game_type->id ?? null,
                    'label' => $game->game_type->label ?? null,
                    'conf' => (bool)($game->game_type->conf ?? false),
                ],
            ];
        }, $games);
    }

    /**
     * @param array<int,\App\Model\Entity\TeamSeasonRosters> $roster
     * @return array<int,array<string,mixed>>
     */
    private function formatRoster(array $roster): array
    {
        return array_map(static function ($entry): array {
            return [
                'id' => (int)$entry->id,
                'roster_number' => $entry->roster_number ?? null,
                'roster_position' => $entry->roster_position ?? null,
                'class_year' => $entry->class_year ?? null,
                'person' => [
                    'id' => $entry->person->id ?? null,
                    'first_name' => $entry->person->first_name ?? null,
                    'last_name' => $entry->person->last_name ?? null,
                    'display' => $entry->person->display ?? null,
                ],
            ];
        }, $roster);
    }

    /**
     * @param array<int,\App\Model\Entity\BlogPost> $posts
     * @return array<int,array<string,mixed>>
     */
    private function formatPosts(array $posts): array
    {
        return array_map(static function ($post): array {
            $tags = [];
            foreach ($post->blog_tags ?? [] as $tag) {
                $tags[] = [
                    'slug' => $tag->slug ?? null,
                    'name' => $tag->name ?? null,
                ];
            }

            return [
                'id' => (int)$post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at,
                'hero_image_id' => $post->hero_image_id,
                'tags' => $tags,
            ];
        }, $posts);
    }
}
