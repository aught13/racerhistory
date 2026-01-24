<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ImageProcessor;
use App\Service\TeamSeasonService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Public Seasons Controller
 *
 * Displays team seasons (filtered to Men's Basketball by default),
 * with related images and blog posts via the tagging system.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class SeasonsController extends AppController
{
    private TeamSeasonService $teamSeasonService;
    private ImageProcessor $imageProcessor;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->teamSeasonService = new TeamSeasonService();
        $this->imageProcessor = new ImageProcessor();
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
    }

    public function index(): void
    {
        $table = $this->fetchTable('TeamSeasons');
        $teamSeasons = $table->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->matching('Teams.Sports', function ($q) {
                return $q->where(['Sports.sport_name' => 'Basketball']);
            })
            ->matching('Teams', function ($q) {
                return $q->where(['Teams.gender' => 'M']);
            })
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();

        $seasonStats = $this->calculateSeasonStats($teamSeasons);

        $this->set(compact('teamSeasons', 'seasonStats'));
    }

    /**
     * @param array<int,\App\Model\Entity\TeamSeason> $teamSeasons
     * @return array<int,array<string,int|float|null>>
     */
    private function calculateSeasonStats(array $teamSeasons): array
    {
        if (empty($teamSeasons)) {
            return [];
        }

        $teamSeasonIds = array_map(static fn($ts) => (int)$ts->id, $teamSeasons);

        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');
        $query = $gamesTable->find();

        $rawStats = $query
            ->select([
                'team_season_id' => 'Games.team_season_id',
                'overall_wins' => $query->newExpr("SUM(CASE WHEN Games.w IN ('1','W') THEN 1 ELSE 0 END)"),
                'overall_losses' => $query->newExpr("SUM(CASE WHEN Games.l IN ('1','L') THEN 1 ELSE 0 END)"),
                'conf_wins' => $query->newExpr("SUM(CASE WHEN GameTypes.conf = 1 AND Games.w IN ('1','W') THEN 1 ELSE 0 END)"),
                'conf_losses' => $query->newExpr("SUM(CASE WHEN GameTypes.conf = 1 AND Games.l IN ('1','L') THEN 1 ELSE 0 END)"),
            ])
            ->where(['Games.team_season_id IN' => $teamSeasonIds])
            ->leftJoinWith('GameTypes')
            ->groupBy(['Games.team_season_id'])
            ->enableHydration(false)
            ->toArray();

        $stats = [];
        foreach ($rawStats as $row) {
            $id = (int)$row['team_season_id'];
            $ow = (int)$row['overall_wins'];
            $ol = (int)$row['overall_losses'];
            $cw = (int)$row['conf_wins'];
            $cl = (int)$row['conf_losses'];

            $stats[$id] = [
                'overall_wins' => $ow,
                'overall_losses' => $ol,
                'overall_pct' => ($ow + $ol) > 0 ? round($ow / ($ow + $ol), 3) : null,
                'conf_wins' => $cw,
                'conf_losses' => $cl,
                'conf_pct' => ($cw + $cl) > 0 ? round($cw / ($cw + $cl), 3) : null,
            ];
        }

        return $stats;
    }

    public function view(int $id): void
    {
        $teamSeason = $this->teamSeasonService->getTeamSeasonById($id);
        if (!$teamSeason) {
            throw new NotFoundException('Season not found');
        }

        $images = $this->imageProcessor->getImagesForTeamSeason($id, 20);
        $blogPosts = $this->getBlogPostsByTag("teamseason-{$id}");
        $games = $this->getGamesForTeamSeason($id);
        $roster = $this->getRosterForTeamSeason($id);

        $this->set(compact('teamSeason', 'images', 'blogPosts', 'games', 'roster'));
    }

    private function getBlogPostsByTag(string $tagSlug): array
    {
        $table = $this->fetchTable('BlogPosts');
        return $table->find()
            ->contain(['BlogTags', 'HeroImages'])
            ->matching('BlogTags', function ($q) use ($tagSlug) {
                return $q->where(['BlogTags.slug' => $tagSlug]);
            })
            ->where(['BlogPosts.is_published' => true])
            ->orderByDesc('BlogPosts.published_at')
            ->limit(10)
            ->all()
            ->toArray();
    }

    private function getGamesForTeamSeason(int $teamSeasonId): array
    {
        $table = $this->fetchTable('Games');
        return $table->find()
            ->contain(['Opponents', 'Places', 'GameTypes'])
            ->where(['Games.team_season_id' => $teamSeasonId])
            ->orderByAsc('Games.game_date')
            ->all()
            ->toArray();
    }

    private function getRosterForTeamSeason(int $teamSeasonId): array
    {
        $table = $this->fetchTable('TeamSeasonRosters');
        return $table->find()
            ->contain(['Persons'])
            ->where(['TeamSeasonRosters.team_season_id' => $teamSeasonId])
            ->orderByAsc('TeamSeasonRosters.roster_number')
            ->all()
            ->toArray();
    }
}
