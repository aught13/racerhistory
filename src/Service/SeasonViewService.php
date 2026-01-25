<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * SeasonViewService
 *
 * Prepares view-model data for the public Seasons view.
 */
class SeasonViewService
{
    private TeamSeasonService $teamSeasonService;
    private ImageProcessor $imageProcessor;
    private StatsService $statsService;
    private BlogPostService $blogPostService;

    /**
     * @param \App\Service\TeamSeasonService|null $teamSeasonService Team season service.
     * @param \App\Service\ImageProcessor|null $imageProcessor Image processor.
     * @param \App\Service\StatsService|null $statsService Stats service.
     * @param \App\Service\BlogPostService|null $blogPostService Blog post service.
     */
    public function __construct(
        ?TeamSeasonService $teamSeasonService = null,
        ?ImageProcessor $imageProcessor = null,
        ?StatsService $statsService = null,
        ?BlogPostService $blogPostService = null,
    ) {
        $this->teamSeasonService = $teamSeasonService ?? new TeamSeasonService();
        $this->imageProcessor = $imageProcessor ?? new ImageProcessor();
        $this->statsService = $statsService ?? new StatsService();
        $this->blogPostService = $blogPostService ?? new BlogPostService();
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

        $images = $this->imageProcessor->getImagesForTeamSeason($teamSeasonId, 24);
        $games = $this->getGamesForTeamSeason($teamSeasonId);
        // Enrich games with computed display fields using GameService
        $gameService = new GameService();
        foreach ($games as $g) {
            try {
                $g->set('result_flag', $gameService->getResultFlag($g));
                $g->set('place_name', $gameService->getPlaceName($g));
                $g->set('place_state', $gameService->getPlaceState($g));
                $g->set('site_name', $gameService->getSiteName($g));
                // opponent prefix: hrn values -> 1: 'Vs', 3: 'vs', else '@'
                $prefix = '@';
                if (!empty($g->hrn) && (int)$g->hrn === 1) {
                    $prefix = 'Vs';
                } elseif (!empty($g->hrn) && (int)$g->hrn === 3) {
                    $prefix = 'vs';
                }
                $g->set('opponent_prefix', $prefix);
            } catch (\Throwable $e) {
                // ignore enrichment errors
            }
        }
        $roster = $this->getRosterForTeamSeason($teamSeasonId);
        $recordSummary = $this->getRecordSummary($teamSeasonId);
        $seasonStats = $this->statsService->getSeasonStats($teamSeasonId);
        $seasonStatsElement = $this->statsService->getSeasonStatsElement($teamSeasonId);
        $seasonStatsColumns = $this->statsService->getSeasonStatsColumns($teamSeasonId, $seasonStats);

        $seasonTag = "teamseason-{$teamSeasonId}";
        $posts = $this->blogPostService->getPublishedByTag($seasonTag, 50);
        $categorized = $this->categorizeSeasonPosts($posts);

        return [
            'teamSeason' => $teamSeason,
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
     * @param int $teamSeasonId
     * @return array<int,\App\Model\Entity\Game>
     */
    private function getGamesForTeamSeason(int $teamSeasonId): array
    {
        $table = TableRegistry::getTableLocator()->get('Games');

        return $table->find()
            ->contain(['Opponents', 'Places', 'GameTypes', 'Sites'])
            ->where(['Games.team_season_id' => $teamSeasonId])
            ->orderByAsc('Games.game_date')
            ->all()
            ->toArray();
    }

    /**
     * @param int $teamSeasonId
     * @return array<int,\App\Model\Entity\TeamSeasonRosters>
     */
    private function getRosterForTeamSeason(int $teamSeasonId): array
    {
        $table = TableRegistry::getTableLocator()->get('TeamSeasonRosters');

        return $table->find()
            ->contain(['Persons'])
            ->where(['TeamSeasonRosters.team_season_id' => $teamSeasonId])
            ->orderByAsc('TeamSeasonRosters.roster_number')
            ->all()
            ->toArray();
    }

    /**
     * Get overall and conference record summary for a team season.
     *
     * @param int $teamSeasonId
     * @return array<string,int|float|null>
     */
    private function getRecordSummary(int $teamSeasonId): array
    {
        $gamesTable = TableRegistry::getTableLocator()->get('Games');
        $query = $gamesTable->find();

        $row = $query
            ->select([
                'overall_wins' => $query->newExpr(
                    "SUM(CASE WHEN Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'overall_losses' => $query->newExpr(
                    "SUM(CASE WHEN Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
                'conf_wins' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'conf_losses' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
            ])
            ->where(['Games.team_season_id' => $teamSeasonId])
            ->leftJoinWith('GameTypes')
            ->enableHydration(false)
            ->first();

        $ow = isset($row['overall_wins']) ? (int)$row['overall_wins'] : 0;
        $ol = isset($row['overall_losses']) ? (int)$row['overall_losses'] : 0;
        $cw = isset($row['conf_wins']) ? (int)$row['conf_wins'] : 0;
        $cl = isset($row['conf_losses']) ? (int)$row['conf_losses'] : 0;

        $overallTotal = $ow + $ol;
        $confTotal = $cw + $cl;

        return [
            'overall_wins' => $ow,
            'overall_losses' => $ol,
            'overall_pct' => $overallTotal > 0 ? round($ow / $overallTotal, 3) : null,
            'conf_wins' => $cw,
            'conf_losses' => $cl,
            'conf_pct' => $confTotal > 0 ? round($cw / $confTotal, 3) : null,
        ];
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
