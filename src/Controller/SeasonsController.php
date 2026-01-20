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

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->teamSeasonService = new TeamSeasonService();
        $this->imageProcessor = new ImageProcessor();
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
     * List all team seasons (Men's Basketball).
     */
    public function index(): void
    {
        // Get men's basketball team seasons
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

        $this->set(compact('teamSeasons'));
    }

    /**
     * View a single team season with related images and blog posts.
     *
     * @param int $id Team season ID
     */
    public function view(int $id): void
    {
        $teamSeason = $this->teamSeasonService->getTeamSeasonById($id);
        if (!$teamSeason) {
            throw new NotFoundException('Season not found');
        }

        // Get related images via tagging
        $images = $this->imageProcessor->getImagesForTeamSeason($id, 20);

        // Get related blog posts via tagging
        $blogPosts = $this->getBlogPostsByTag("teamseason-{$id}");

        // Get games for this team season
        $games = $this->getGamesForTeamSeason($id);

        // Get roster
        $roster = $this->getRosterForTeamSeason($id);

        $this->set(compact('teamSeason', 'images', 'blogPosts', 'games', 'roster'));
    }

    /**
     * Get blog posts by tag slug.
     *
     * @param string $tagSlug Tag slug
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    private function getBlogPostsByTag(string $tagSlug): array
    {
        $table = $this->fetchTable('BlogPosts');
        $posts = $table->find()
            ->contain(['BlogTags', 'HeroImages'])
            ->matching('BlogTags', function ($q) use ($tagSlug) {
                return $q->where(['BlogTags.slug' => $tagSlug]);
            })
            ->where(['BlogPosts.is_published' => true])
            ->orderByDesc('BlogPosts.published_at')
            ->limit(10)
            ->all()
            ->toArray();

        return $posts;
    }

    /**
     * Get games for a team season.
     *
     * @param int $teamSeasonId Team season ID
     * @return array<int,\App\Model\Entity\Game>
     */
    private function getGamesForTeamSeason(int $teamSeasonId): array
    {
        $table = $this->fetchTable('Games');
        $games = $table->find()
            ->contain(['Opponents', 'Places', 'GameTypes'])
            ->where(['Games.team_season_id' => $teamSeasonId])
            ->orderByAsc('Games.game_date')
            ->all()
            ->toArray();

        return $games;
    }

    /**
     * Get roster for a team season.
     *
     * @param int $teamSeasonId Team season ID
     * @return array<int,\App\Model\Entity\TeamSeasonRosters>
     */
    private function getRosterForTeamSeason(int $teamSeasonId): array
    {
        $table = $this->fetchTable('TeamSeasonRosters');
        $roster = $table->find()
            ->contain(['Persons'])
            ->where(['TeamSeasonRosters.team_season_id' => $teamSeasonId])
            ->orderByAsc('TeamSeasonRosters.roster_number')
            ->all()
            ->toArray();

        return $roster;
    }
}
