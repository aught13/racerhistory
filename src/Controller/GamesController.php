<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BasketballStatsService;
use App\Service\ImageProcessor;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Public Games Controller
 *
 * Displays games and box scores.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class GamesController extends AppController
{
    private ?BasketballStatsService $basketballStatsService = null;
    private ImageProcessor $imageProcessor;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->basketballStatsService = new BasketballStatsService();
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
     * List recent games.
     */
    public function index(): void
    {
        $table = $this->fetchTable('Games');
        $games = $table->find()
            ->contain(['Opponents', 'Places', 'GameTypes', 'TeamSeason' => ['Teams', 'Seasons']])
            ->matching('TeamSeason.Teams', function ($q) {
                return $q->where([
                    'Teams.sport_id' => 1, // Basketball
                    'Teams.gender' => 'M',
                ]);
            })
            ->orderByDesc('Games.game_date')
            ->limit(100)
            ->all()
            ->toArray();

        $this->set(compact('games'));
    }

    /**
     * View a single game with box score if available.
     *
     * @param int $id Game ID
     */
    public function view(int $id): void
    {
        $table = $this->fetchTable('Games');
        $game = $table->find()
            ->contain(['Opponents', 'Places', 'GameTypes', 'TeamSeason' => ['Teams', 'Seasons']])
            ->where(['Games.id' => $id])
            ->first();

        if (!$game) {
            throw new NotFoundException('Game not found');
        }

        // Try to get box score data
        $boxScore = null;
        if ($this->basketballStatsService) {
            try {
                $boxScore = $this->basketballStatsService->getGameStats($id);
            } catch (\Exception $e) {
                // No box score available
            }
        }

        // Get related images
        $images = $this->imageProcessor->getImagesByAllTags(["game-{$id}"], 10);

        // Get related blog posts
        $blogPosts = $this->getBlogPostsByTag("game-{$id}");

        $this->set(compact('game', 'boxScore', 'images', 'blogPosts'));
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
}
