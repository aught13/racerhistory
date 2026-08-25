<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\TeamSeason;
use Cake\ORM\TableRegistry;
use Throwable;

/**
 * SeasonViewService
 *
 * Prepares view-model data for the public Seasons view.
 */
class SeasonViewService
{
    private TeamSeasonService $teamSeasonService;
    private TeamSportContextService $teamSportContextService;
    private ImageTagService $imageTagService;
    private StatsService $statsService;
    private BlogPostService $blogPostService;
    private GameService $gameService;
    private TeamSeasonRosterService $teamSeasonRosterService;

    /**
     * @param \App\Service\TeamSeasonService|null $teamSeasonService Team season service.
     * @param \App\Service\ImageTagService|null $imageTagService Image tag service.
     * @param \App\Service\StatsService|null $statsService Stats service.
     * @param \App\Service\BlogPostService|null $blogPostService Blog post service.
     * @param \App\Service\GameService|null $gameService Game service.
     * @param \App\Service\TeamSeasonRosterService|null $teamSeasonRosterService Roster service.
     */
    public function __construct(
        ?TeamSeasonService $teamSeasonService = null,
        ?ImageTagService $imageTagService = null,
        ?StatsService $statsService = null,
        ?BlogPostService $blogPostService = null,
        ?GameService $gameService = null,
        ?TeamSeasonRosterService $teamSeasonRosterService = null,
    ) {
        $this->teamSeasonService = $teamSeasonService ?? new TeamSeasonService();
        $this->teamSportContextService = new TeamSportContextService();
        $this->imageTagService = $imageTagService ?? new ImageTagService();
        $this->statsService = $statsService ?? new StatsService();
        $this->blogPostService = $blogPostService ?? new BlogPostService();
        $this->gameService = $gameService ?? new GameService();
        $this->teamSeasonRosterService = $teamSeasonRosterService ?? new TeamSeasonRosterService();
    }

    /**
     * Build view variables for the public season page.
     *
     * @param int $teamSeasonId
     * @return array<string,mixed>
     */
    public function getViewData(int $teamSeasonId): array
    {
        $teamSeason = $this->teamSeasonService->getTeamSeasonById($teamSeasonId);
        if (!$teamSeason) {
            return ['teamSeason' => null];
        }

        $previousTeamSeason = $this->resolveAdjacentTeamSeason($teamSeason, 'previous');
        $nextTeamSeason = $this->resolveAdjacentTeamSeason($teamSeason, 'next');

        $images = $this->imageTagService->getImagesForTeamSeason($teamSeasonId, 24);
        $games = $this->gameService->getGamesByTeamSeason($teamSeasonId, 'ASC');
    // Enrich games with computed display fields using GameService
        foreach ($games as $g) {
            try {
                $g->set('result_flag', $this->gameService->getResultFlag($g));
                $g->set('place_city', $this->gameService->getPlaceName($g));
                $g->set('place_state', $this->gameService->getPlaceState($g));
                $g->set('site_name', $this->gameService->getSiteName($g));
                // opponent prefix: hrn values -> 1: 'Vs', 3: 'vs', else '@'
                $prefix = '@';
                if (!empty($g->hrn) && (int)$g->hrn === 1) {
                    $prefix = 'Vs';
                } elseif (!empty($g->hrn) && (int)$g->hrn === 3) {
                    $prefix = 'vs';
                }
                $g->set('opponent_prefix', $prefix);
            } catch (Throwable $e) {
                // ignore enrichment errors
            }
        }
        $roster = $this->teamSeasonRosterService->getRosterForTeamSeason($teamSeasonId);
        $recordSummary = $this->teamSeasonService->getRecordSummary($teamSeasonId);
        $seasonStats = $this->statsService->getSeasonStats($teamSeasonId);
        $seasonStatsElement = $this->statsService->getSeasonStatsElement($teamSeasonId);
        $seasonStatsColumns = $this->statsService->getSeasonStatsColumns($teamSeasonId, $seasonStats);

        $seasonTag = "teamseason-{$teamSeasonId}";
        $posts = $this->blogPostService->getPublishedByTag($seasonTag, 50);
        $categorized = $this->categorizeSeasonPosts($posts);

        return [
            'teamSeason' => $teamSeason,
            'previousTeamSeason' => $previousTeamSeason,
            'nextTeamSeason' => $nextTeamSeason,
            'images' => $images,
            'games' => $games,
            'roster' => $roster,
            'recordSummary' => $recordSummary,
            'seasonStats' => $seasonStats,
            'seasonStatsElement' => $seasonStatsElement,
            'seasonStatsColumns' => $seasonStatsColumns,
            'previewPosts' => $categorized['preview'],
            'reviewPosts' => $categorized['review'],
            'otherPosts' => $categorized['other'],
        ];
    }

    /**
     * @param \App\Model\Entity\TeamSeason $teamSeason
     * @param 'previous'|'next' $direction
     * @return \App\Model\Entity\TeamSeason|null
     */
    private function resolveAdjacentTeamSeason(object $teamSeason, string $direction): ?object
    {
        $team = $teamSeason->team ?? null;
        $sportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
        $seasonEnd = (int)($teamSeason->season->end ?? 0);

        if ($sportKey === null || $seasonEnd <= 0) {
            return null;
        }

        $teamSeasonsTable = TableRegistry::getTableLocator()->get('TeamSeasons');
        $query = $teamSeasonsTable->find()
            ->contain(['Teams', 'Seasons'])
            ->matching('Teams', function ($teamsQuery) use ($sportKey) {
                return $teamsQuery->where([
                    'OR' => [
                        ['Teams.sport_key' => $sportKey],
                        ['Teams.sport_key IS' => null],
                    ],
                ]);
            })
            ->matching('Seasons', function ($seasonsQuery) use ($seasonEnd, $direction) {
                if ($direction === 'previous') {
                    return $seasonsQuery->where(['Seasons.end <' => $seasonEnd]);
                }

                return $seasonsQuery->where(['Seasons.end >' => $seasonEnd]);
            })
            ->where(['TeamSeasons.id !=' => $teamSeason->id]);

        $query->orderBy($direction === 'previous' ? ['Seasons.end' => 'DESC'] : ['Seasons.end' => 'ASC']);

        $adjacent = $query->first();

        return $adjacent instanceof TeamSeason ? $adjacent : null;
    }

    /**
     * @param array<int,\App\Model\Entity\BlogPost> $posts
     * @return array{preview:array<int,\App\Model\Entity\BlogPost>,review:array<int,\App\Model\Entity\BlogPost>,other:array<int,\App\Model\Entity\BlogPost>}
     */
    private function categorizeSeasonPosts(array $posts): array
    {
        $preview = [];
        $review = [];
        $other = [];

        foreach ($posts as $post) {
            $hasPreview = $this->postHasTag($post, 'preview');
            $hasReview = $this->postHasTag($post, 'review');

            if ($hasPreview) {
                $preview[] = $post;
            }
            if ($hasReview) {
                $review[] = $post;
            }
            if (!$hasPreview && !$hasReview) {
                $other[] = $post;
            }
        }

        return [
            'preview' => $preview,
            'review' => $review,
            'other' => $other,
        ];
    }

    /**
     * @param \App\Model\Entity\BlogPost $post
     * @param string $slug
     * @return bool
     */
    private function postHasTag(object $post, string $slug): bool
    {
        $slug = mb_strtolower($slug);
        $tags = $post->blog_tags ?? [];
        foreach ($tags as $tag) {
            $tagSlug = (string)($tag->slug ?? '');
            if ($tagSlug !== '' && mb_strtolower($tagSlug) === $slug) {
                return true;
            }
        }

        return false;
    }
}
