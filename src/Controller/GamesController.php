<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BlogPostService;
use App\Service\GameService;
use App\Service\GameViewService;
use App\Service\ImageTagService;
use App\Service\StatsService;
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
    private GameService $gameService;
    private GameViewService $gameViewService;
    private StatsService $statsService;
    private ImageTagService $imageTagService;
    private BlogPostService $blogPostService;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->gameService = new GameService();
        $this->gameViewService = new GameViewService($this->gameService);
        $this->statsService = new StatsService();
        $this->imageTagService = new ImageTagService();
        $this->blogPostService = new BlogPostService();
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

        // Enrich games with computed display fields
        foreach ($games as $g) {
            try {
                $g->set('result_flag', $this->gameService->getResultFlag($g));
                $g->set('place_name', $this->gameService->getPlaceName($g));
                $g->set('place_state', $this->gameService->getPlaceState($g));
                $g->set('site_name', $this->gameService->getSiteName($g));
                $prefix = '@';
                if (!empty($g->hrn) && (int)$g->hrn === 1) {
                    $prefix = 'Vs';
                } elseif (!empty($g->hrn) && (int)$g->hrn === 3) {
                    $prefix = 'vs';
                }
                $g->set('opponent_prefix', $prefix);
            } catch (\Throwable $e) {
            }
        }

        $this->set(compact('games'));
    }

    /**
     * View a single game with box score if available.
     *
     * @param int $id Game ID
     */
    public function view(int $id): void
    {
        $viewData = $this->buildGameViewData($id);

        $this->set($viewData);
    }

    /**
     * Render stats frame for Turbo requests.
     *
     * @param int $id Game ID
     * @return void
     */
    public function stats(int $id): void
    {
        $viewData = $this->buildGameViewData($id);

        $this->viewBuilder()
            ->setLayout(null)
            ->setTemplate('stats');

        $this->set($viewData);
    }

    /**
     * @param int $id Game ID
     * @return array<string,mixed>
     */
    private function buildGameViewData(int $id): array
    {
        try {
            $viewData = $this->gameViewService->getViewData($id);
        } catch (\Throwable $e) {
            throw new NotFoundException('Game not found');
        }

        $game = $viewData['game'] ?? null;
        if (!$game) {
            throw new NotFoundException('Game not found');
        }

        $this->enrichGameDisplay($game);

        $statsElement = $this->statsService->getGameStatsElement($id);
        $images = $this->imageTagService->getImagesByAllTags(["game-{$id}"], 12);
        $blogPosts = $this->blogPostService->getPublishedByTag("game-{$id}", 12);

        return $viewData + compact('game', 'statsElement', 'images', 'blogPosts');
    }

    /**
     * @param \App\Model\Entity\Game $game Game entity
     * @return void
     */
    private function enrichGameDisplay(object $game): void
    {
        try {
            $game->set('result_flag', $this->gameService->getResultFlag($game));
            $game->set('place_name', $this->gameService->getPlaceName($game));
            $game->set('place_state', $this->gameService->getPlaceState($game));
            $game->set('site_name', $this->gameService->getSiteName($game));
            $prefix = '@';
            if (!empty($game->hrn) && (int)$game->hrn === 1) {
                $prefix = 'Vs';
            } elseif (!empty($game->hrn) && (int)$game->hrn === 3) {
                $prefix = 'vs';
            }
            $game->set('opponent_prefix', $prefix);
        } catch (\Throwable $e) {
            // ignore enrichment errors
        }
    }
}
